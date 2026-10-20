export function registerProfilePhotoCrop(Alpine) {
    if (!Alpine || Alpine._profilePhotoCropRegistered) {
        return;
    }

    Alpine._profilePhotoCropRegistered = true;

    Alpine.data('profilePhotoCrop', (config = {}) => ({
        open: false,
        src: null,
        naturalWidth: 0,
        naturalHeight: 0,
        viewSize: Number(config.viewSize || 288),
        outputSize: Number(config.outputSize || 512),
        scale: 1,
        minScale: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        dragStartX: 0,
        dragStartY: 0,
        originX: 0,
        originY: 0,
        saving: false,
        error: null,
        savingLabel: config.savingLabel || '…',
        errorLabel: config.errorLabel || 'Error',
        objectUrl: null,

        openFromFile(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            this.error = null;

            if (!file) {
                return;
            }

            if (!String(file.type || '').startsWith('image/')) {
                this.error = this.errorLabel;
                return;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
            }

            this.objectUrl = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
                this.naturalWidth = image.naturalWidth;
                this.naturalHeight = image.naturalHeight;
                this.src = this.objectUrl;
                this.fitCover();
                this.open = true;
            };
            image.onerror = () => {
                this.error = this.errorLabel;
            };
            image.src = this.objectUrl;
        },

        fitCover() {
            const cover = Math.max(
                this.viewSize / this.naturalWidth,
                this.viewSize / this.naturalHeight,
            );
            this.minScale = cover;
            this.scale = cover;
            this.offsetX = (this.viewSize - this.naturalWidth * cover) / 2;
            this.offsetY = (this.viewSize - this.naturalHeight * cover) / 2;
        },

        imageStyle() {
            return `width:${this.naturalWidth}px;height:${this.naturalHeight}px;transform:translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale});transform-origin:0 0;`;
        },

        previewStyle() {
            const ratio = 96 / this.viewSize;
            return `width:${this.naturalWidth}px;height:${this.naturalHeight}px;transform:translate(${this.offsetX * ratio}px, ${this.offsetY * ratio}px) scale(${this.scale * ratio});transform-origin:0 0;`;
        },

        setZoomPercent(percent) {
            const next = this.minScale * (Number(percent) / 100);
            this.zoomTo(next, this.viewSize / 2, this.viewSize / 2);
        },

        onWheel(event) {
            const delta = event.deltaY > 0 ? 0.94 : 1.06;
            const rect = event.currentTarget.getBoundingClientRect();
            this.zoomTo(this.scale * delta, event.clientX - rect.left, event.clientY - rect.top);
        },

        zoomTo(nextScale, pivotX, pivotY) {
            const clamped = Math.min(this.minScale * 3, Math.max(this.minScale, nextScale));
            const ratio = clamped / this.scale;
            this.offsetX = pivotX - (pivotX - this.offsetX) * ratio;
            this.offsetY = pivotY - (pivotY - this.offsetY) * ratio;
            this.scale = clamped;
            this.clampOffsets();
        },

        startDrag(event) {
            if (!this.src) {
                return;
            }
            this.dragging = true;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.originX = this.offsetX;
            this.originY = this.offsetY;
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        onDrag(event) {
            if (!this.dragging) {
                return;
            }
            this.offsetX = this.originX + (event.clientX - this.dragStartX);
            this.offsetY = this.originY + (event.clientY - this.dragStartY);
            this.clampOffsets();
        },

        endDrag() {
            this.dragging = false;
        },

        clampOffsets() {
            const width = this.naturalWidth * this.scale;
            const height = this.naturalHeight * this.scale;

            if (width <= this.viewSize) {
                this.offsetX = (this.viewSize - width) / 2;
            } else {
                this.offsetX = Math.min(0, Math.max(this.viewSize - width, this.offsetX));
            }

            if (height <= this.viewSize) {
                this.offsetY = (this.viewSize - height) / 2;
            } else {
                this.offsetY = Math.min(0, Math.max(this.viewSize - height, this.offsetY));
            }
        },

        async applyAndSave() {
            if (!this.src || this.saving) {
                return;
            }

            this.saving = true;
            this.error = null;

            try {
                const canvas = document.createElement('canvas');
                canvas.width = this.outputSize;
                canvas.height = this.outputSize;
                const ctx = canvas.getContext('2d');
                const ratio = this.outputSize / this.viewSize;

                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(
                    await this.loadImage(this.src),
                    this.offsetX * ratio,
                    this.offsetY * ratio,
                    this.naturalWidth * this.scale * ratio,
                    this.naturalHeight * this.scale * ratio,
                );

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob((result) => {
                        if (result) {
                            resolve(result);
                        } else {
                            reject(new Error('blob'));
                        }
                    }, 'image/jpeg', 0.92);
                });

                const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

                await new Promise((resolve, reject) => {
                    this.$wire.upload(
                        'photo',
                        file,
                        () => resolve(),
                        () => reject(new Error('upload')),
                        () => {},
                    );
                });

                const ok = await this.$wire.savePhoto();
                if (!ok) {
                    this.saving = false;
                    return;
                }
            } catch (e) {
                this.error = this.errorLabel;
                this.saving = false;
                return;
            }

            this.saving = false;
            this.close();
        },

        loadImage(src) {
            return new Promise((resolve, reject) => {
                const image = new Image();
                image.onload = () => resolve(image);
                image.onerror = reject;
                image.src = src;
            });
        },

        onSaved() {
            this.saving = false;
            this.close();
        },

        close() {
            this.open = false;
            this.dragging = false;
            this.src = null;
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }
            try {
                this.$wire?.set('photo', null);
            } catch {
                // ignore
            }
        },
    }));
}
