@component('mail::message')
# Yêu cầu đặt hàng mới

**Tên khách:** {{ $inquiry->name }}
**SĐT:** {{ $inquiry->phone }}
@if($inquiry->email)
**Email khách:** {{ $inquiry->email }}
@endif
**Kênh liên hệ chọn:** {{ $inquiry->channel->value }}

---

{{ $inquiry->message }}

---

@component('mail::button', ['url' => 'tel:' . $inquiry->phone])
Gọi cho khách
@endcomponent

Cảm ơn,<br>
{{ config('app.name') }}
@endcomponent
