{{--
    Weekly opening hours for a clinic profile. Groups the raw rows by weekday
    (a day can have two rows for a lunch break) and marks the current day.
--}}
@props(['clinic'])

@php
    use App\Models\OpeningHour;

    $byDay = $clinic->openingHours->groupBy('day_of_week');
    // Carbon: 0 = Sunday..6 = Saturday. Our scheme: 0 = Monday..6 = Sunday.
    $todayIndex = (now()->dayOfWeek + 6) % 7;
@endphp

<table class="w-full text-sm">
    <tbody>
        @foreach (OpeningHour::DAYS_RO as $index => $label)
            @php $rows = $byDay->get($index); @endphp
            <tr @class(['border-b last:border-0', 'font-medium' => $index === $todayIndex])>
                <th scope="row" class="py-2 pr-4 text-left font-normal">
                    {{ $label }}
                    @if ($index === $todayIndex)
                        <span class="text-muted-foreground">(azi)</span>
                    @endif
                </th>
                <td class="py-2 text-right tabular-nums">
                    @if (! $rows || $rows->every(fn ($r) => $r->is_closed))
                        <span class="text-muted-foreground">Închis</span>
                    @else
                        {{ $rows
                            ->filter(fn ($r) => ! $r->is_closed && $r->opens_at && $r->closes_at)
                            ->map(fn ($r) => substr((string) $r->opens_at, 0, 5).'–'.substr((string) $r->closes_at, 0, 5))
                            ->implode(', ') }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
