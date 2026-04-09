@extends('admin.layout')
@section('title', 'Слоты')

@section('content')
<h1 class="section-heading">🕐 Управление слотами</h1>

{{-- Add new slot --}}
<div class="card">
    <div class="card-title">✨ Добавить новый слот</div>
    <form method="POST" action="{{ route('admin.slots.store', [], false) }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>День недели</label>
                <select name="day_of_week" required>
                    @foreach(\App\Models\Slot::DAY_NAMES as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Время начала</label>
                <input type="time" name="start_time" required>
            </div>
            <div class="form-group">
                <label>Длительность (мин)</label>
                <input type="number" name="duration_minutes" value="30" min="5" max="480" required>
            </div>
            <div class="form-group">
                <label>Meeting URL (опц)</label>
                <input type="url" name="default_meeting_url" placeholder="https://...">
            </div>
            <div class="form-group" style="flex:0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Создать</button>
            </div>
        </div>
    </form>
</div>

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
                <td>
                    <select name="day_of_week" form="update-{{ $slot->id }}" style="width:auto;">
                        @foreach(\App\Models\Slot::DAY_NAMES as $id => $name)
                        <option value="{{ $id }}" @selected($slot->day_of_week == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="time" name="start_time" value="{{ $slot->formatted_time }}" form="update-{{ $slot->id }}" style="width:100px;">
                </td>
                <td>
                   <input type="number" name="duration_minutes" value="{{ $slot->duration_minutes }}" form="update-{{ $slot->id }}" style="width:60px;" min="5" max="480">
                   мин.
                </td>
                <td>
                    <div style="display:flex;flex-direction:column;gap:.3rem;">
                        <input type="url" name="default_meeting_url" value="{{ $slot->default_meeting_url }}" form="update-{{ $slot->id }}"
                            placeholder="https://meet.example.com/room" style="min-width:200px;">
                        <label style="display:flex;align-items:center;gap:.3rem;white-space:nowrap;margin:0;color:var(--muted);font-size:.8rem;">
                            <input type="checkbox" name="is_active" value="1" @checked($slot->is_active) form="update-{{ $slot->id }}" style="width:auto;"> Активен
                        </label>
                    </div>
                </td>
                <td style="white-space:nowrap;">
                    <form id="update-{{ $slot->id }}" method="POST" action="{{ route('admin.slots.update', $slot, [], false) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary btn-sm" title="Сохранить">💾</button>
                    </form>
                    <form method="POST" action="{{ route('admin.slots.delete', $slot, [], false) }}" style="display:inline;" onsubmit="return confirm('Удалить этот слот?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm" title="Удалить">🗑️</button>
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
    <form method="POST" action="{{ route('admin.slots.block', [], false) }}">
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
                    <form method="POST" action="{{ route('admin.slots.unblock', $block, [], false) }}" onsubmit="return confirm('Разблокировать?')">
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
    <div class="card-title">📅 Записи на {{ config('app.booking_days_limit') }} дней</div>
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
                <td><a href="{{ route('admin.bookings', [], false) }}" class="btn btn-ghost btn-sm">Детали</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection