<table class="head">
    <tr>
        <td>
            <h2 class="section-kicker">{{ $title }}</h2>
            @if ($continued)
                <p class="section-meta">continuação</p>
            @elseif ($filterNote !== '')
                <p class="section-meta">{{ $filterNote }}</p>
            @else
                <p class="section-meta">{{ $regionalName }}</p>
            @endif
        </td>
        <td class="year">{{ $year }}</td>
    </tr>
</table>
