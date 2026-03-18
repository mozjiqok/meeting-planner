@extends('admin.layout')
@section('title', 'Слоты')

@section('content')
<h1 class="section-heading">🕐 Управление слотами</h1>

{{-- Slot list with meeting URLs --}}
<div class="card">
    <div class="card-title">Настройка слотов</div>
    <table>
        <thead>
            <tr>
                <th>День</th>
                <th>Время начала</th>
                <th>Длительность</th>
                <th>Настройки</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($slots as $slot)
            <tr>
                <td>{{ $slot->day_name }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.slots.update', $slot) }}" style="display:flex;gap:.5rem;align-items:center;">
                        @csrf @method('PATCH')
                        <input type="time" name="start_time" value="{{ $slot->formatted_time }}" style="width:100px;">
                </td>
                <td>
                   <input type="number" name="duration_minutes" value="{{ $slot->duration_minutes }}" style="width:60px;" min="5" max="480">
                   мин.
                </td>
                <td>
                    <input type="url" name="default_meeting_url" value="{{ $slot->default_meeting_url }}"
                        placeholder="https://meet.example.com/room" style="min-width:200px;">
                    <label style="display:flex;align-items:center;gap:.3rem;white-space:nowrap;margin:0;color:var(--muted);font-size:.8rem;">
                        <input type="checkbox" name="is_active" value="1" @checked($slot->is_active) style="width:auto;"> Активен
                    </label>
                </td>
                <td>
                    <button type="submit" class="btn btn-primary btn-sm">💾</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Block a slot --}}
<div class="card">
    <div class="card-title">🔒 Заблокировать конкретный слот</div>
    <form method="POST" action="{{ route('admin.slots.block') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Слот</label>
                <select name="slot_id">
                    @foreach($slots as $slot)
                    <option value="{{ $slot->id }}">{{ $slot->day_name }} — {{ $slot->formatted_time }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Дата</label>
                <input type="date" name="blocked_date" min="{{ today(config('app.timezone'))->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label>Причина (необязательно)</label>
                <input type="text" name="reason" placeholder="Праздник, болезнь...">
            </div>
            <div class="form-group" style="flex:0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Заблокировать</button>
            </div>
        </div>
    </form>
</div>

{{-- Active blocks --}}
@if($blocks->isNotEmpty())
<div class="card">
    <div class="card-title">Активные блокировки</div>
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Слот</th>
                <th>Причина</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($blocks as $block)
            <tr>
                <td>{{ \Carbon\Carbon::parse($block->blocked_date)->locale('ru')->isoFormat('D MMMM YYYY (ddd)') }}</td>
                <td>{{ $block->slot->formatted_time }}</td>
                <td class="text-muted">{{ $block->reason ?: '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.slots.unblock', $block) }}" onsubmit="return confirm('Разблокировать?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Снять</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Upcoming bookings calendar view --}}
<div class="card">
    <div class="card-title">📅 Записи на 14 дней</div>
    @if($bookings->isEmpty())
    <div class="empty">Нет записей</div>
    @else
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Время</th>
                <th>Подписчик</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
            <tr>
                <td>{{ $booking->booking_date->locale('ru')->isoFormat('D MMMM (ddd)') }}</td>
                <td><strong>{{ $booking->formatted_time }}</strong></td>
                <td>
                    @if($booking->telegram_username)
                    {{ '@' . $booking->telegram_username }}
                    @else
                    {{ $booking->telegram_first_name }}
                    @endif
                </td>
                <td><a href="{{ route('admin.bookings') }}" class="btn btn-ghost btn-sm">Детали</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection