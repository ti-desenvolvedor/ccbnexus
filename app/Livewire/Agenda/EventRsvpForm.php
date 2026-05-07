<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventRsvpForm extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public int $step = 1;

    public string $participation = EventRsvp::PARTICIPATION_NOT_ANSWERED;

    public ?string $presence_mode = null;

    public bool $meal_coffee = false;

    public bool $meal_lunch = false;

    public bool $meal_snack = false;

    public bool $meal_dinner = false;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->authorize('respond', $this->event);

        $existing = EventRsvp::query()
            ->where('event_id', $this->event->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $this->participation = $existing->participation;
            $this->presence_mode = $existing->presence_mode;
            $this->meal_coffee = $existing->meal_coffee;
            $this->meal_lunch = $existing->meal_lunch;
            $this->meal_snack = $existing->meal_snack;
            $this->meal_dinner = $existing->meal_dinner;
        }
    }

    public function proceedFromStep1(): void
    {
        $this->authorize('respond', $this->event);

        $rules = [
            'participation' => ['required', 'in:yes,no,maybe'],
            'presence_mode' => [
                'nullable',
                'in:in_person,online',
                Rule::requiredIf(
                    $this->event->attendance_mode === 'hybrid' && $this->participation === 'yes'
                ),
            ],
        ];

        $this->validate($rules, [], [
            'participation' => 'confirmação',
            'presence_mode' => 'modo de participação',
        ]);

        if ($this->needsMealStep()) {
            $this->step = 2;

            return;
        }

        $this->persistAndFinish();
    }

    public function backToStep1(): void
    {
        $this->step = 1;
    }

    public function saveMealsAndFinish(): void
    {
        $this->authorize('respond', $this->event);
        $this->persistAndFinish();
    }

    protected function needsMealStep(): bool
    {
        if ($this->participation !== 'yes') {
            return false;
        }

        if (! $this->event->needs_meals) {
            return false;
        }

        if (! $this->event->meal_coffee && ! $this->event->meal_lunch && ! $this->event->meal_snack && ! $this->event->meal_dinner) {
            return false;
        }

        if ($this->event->attendance_mode === 'online_only') {
            return false;
        }

        if ($this->event->attendance_mode === 'in_person') {
            return true;
        }

        return $this->presence_mode === EventRsvp::PRESENCE_IN_PERSON;
    }

    protected function persistAndFinish(): void
    {
        $presence = $this->resolvedPresenceModeForStorage();
        $meals = $this->resolvedMealFlagsForStorage();

        EventRsvp::query()->updateOrCreate(
            [
                'event_id' => $this->event->id,
                'user_id' => auth()->id(),
            ],
            [
                'participation' => $this->participation,
                'presence_mode' => $presence,
                'meal_coffee' => $meals['coffee'],
                'meal_lunch' => $meals['lunch'],
                'meal_snack' => $meals['snack'],
                'meal_dinner' => $meals['dinner'],
            ]
        );

        session()->flash('status', __('Confirmação guardada.'));
        $this->redirect(route('agenda.events.index'), navigate: true);
    }

    /**
     * @return array{coffee: bool, lunch: bool, snack: bool, dinner: bool}
     */
    protected function resolvedMealFlagsForStorage(): array
    {
        $zero = ['coffee' => false, 'lunch' => false, 'snack' => false, 'dinner' => false];

        if ($this->participation !== 'yes') {
            return $zero;
        }

        if (! $this->needsMealStep()) {
            return $zero;
        }

        return [
            'coffee' => $this->event->meal_coffee && $this->meal_coffee,
            'lunch' => $this->event->meal_lunch && $this->meal_lunch,
            'snack' => $this->event->meal_snack && $this->meal_snack,
            'dinner' => $this->event->meal_dinner && $this->meal_dinner,
        ];
    }

    protected function resolvedPresenceModeForStorage(): ?string
    {
        if ($this->participation !== 'yes') {
            return null;
        }

        return match ($this->event->attendance_mode) {
            'online_only' => EventRsvp::PRESENCE_ONLINE,
            'in_person' => EventRsvp::PRESENCE_IN_PERSON,
            'hybrid' => $this->presence_mode,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.agenda.event-rsvp-form');
    }
}
