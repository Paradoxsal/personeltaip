@extends('layouts.user_type.auth')
{{-- Yukarıdaki layout'u projenizdeki Soft UI Dashboard vs. yapınıza göre ayarlayın --}}

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Ban Yönetimi / Cihaz Düzenleme</h4>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Kullanıcı Adı</th>
                <th>Email</th>
                <th>Ban Durumu</th>
                <th>Ban Sebebi</th>
                <th>Cihaz Yetki</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            @php
                // Hazırlık
                $banStatus   = $user->banned ? 'Banlı' : 'Aktif';
                $banBadge    = $user->banned ? 'bg-danger' : 'bg-success';
                $yetkiStatus = $user->cihaz_yetki ? 'YETKİLİ' : 'YETKİSİZ';
                $yetkiBadge  = $user->cihaz_yetki ? 'bg-success' : 'bg-secondary';
            @endphp
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>

                {{-- Ban Durumu --}}
                <td>
                    <span class="badge {{ $banBadge }}">{{ $banStatus }}</span>
                </td>

                {{-- Ban Sebebi --}}
                <td>
                    @if($user->banned && $user->banned_log)
                      {{ $user->banned_log }}
                    @else
                      <span class="text-muted">-</span>
                    @endif
                </td>

                {{-- Cihaz Yetki --}}
                <td>
                    <span class="badge {{ $yetkiBadge }}">
                        {{ $yetkiStatus }}
                    </span>
                </td>

                {{-- İşlem (Düzenle) --}}
                <td>
                    <button class="btn btn-sm btn-info"
                            data-bs-toggle="modal"
                            data-bs-target="#editUserModal-{{ $user->id }}">
                        Düzenle
                    </button>
                </td>
            </tr>

            <!-- Düzenle Modalı -->
            <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('ban.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Kullanıcı Düzenle: {{ $user->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                {{-- Ban Durumu --}}
                                <div class="mb-3">
                                    <label for="banned_{{ $user->id }}" class="form-label">Banlı mı?</label>
                                    <select name="banned" id="banned_{{ $user->id }}" class="form-select">
                                        <option value="0" {{ $user->banned ? '' : 'selected' }}>Hayır</option>
                                        <option value="1" {{ $user->banned ? 'selected' : '' }}>Evet</option>
                                    </select>
                                </div>

                                {{-- Ban Sebebi --}}
                                <div class="mb-3">
                                    <label for="ban_reason_{{ $user->id }}" class="form-label">Ban Sebebi</label>
                                    <input type="text" 
                                           name="ban_reason" 
                                           id="ban_reason_{{ $user->id }}" 
                                           class="form-control"
                                           value="{{ $user->banned_log }}">
                                </div>

                                {{-- Cihaz Yetki --}}
                                <div class="mb-3">
                                    <label for="cihaz_yetki_{{ $user->id }}" class="form-label">Cihaz Yetki</label>
                                    <select name="cihaz_yetki" id="cihaz_yetki_{{ $user->id }}" class="form-select">
                                        <option value="0" {{ $user->cihaz_yetki ? '' : 'selected' }}>Yetkisiz</option>
                                        <option value="1" {{ $user->cihaz_yetki ? 'selected' : '' }}>Yetkili</option>
                                    </select>
                                </div>

                                {{-- Mevcut Device Info (disabled) --}}
                                <div class="mb-3">
                                    <label class="form-label">Mevcut Cihaz Bilgisi</label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="{{ $user->device_info ?? 'Yok' }}" 
                                           disabled>
                                    <input type="hidden" name="device_info" value="{{ $user->device_info }}">
                                </div>

                                {{-- Seçenek: Yeni cihaz mı, eski cihaza mı dön, vs. --}}
                                <div class="mb-3">
                                    <label for="device_option_{{ $user->id }}" class="form-label">
                                        Cihaz Ayarı
                                    </label>
                                    <select name="device_option" id="device_option_{{ $user->id }}"
                                        class="form-select"
                                        onchange="toggleOldDeviceInput({{ $user->id }})">
                                        <option value="keep_current">Mevcut cihazı koru</option>
                                        <option value="new_device">Yeni cihaz ile ayarla (sıfırla)</option>
                                        <option value="ban_kaldir_eski">Ban kaldır ve eski cihaza dön</option>
                                    </select>
                                </div>

                                {{-- Eski Device Info (gizli text alanı) --}}
                                <div class="mb-3" id="oldDeviceDiv-{{ $user->id }}" style="display:none;">
                                    <label class="form-label">Eski Device Info Girin</label>
                                    <input type="text" name="old_device_info" class="form-control"
                                           placeholder="Örneğin samsung_A50_id12345">
                                </div>
                            </div> <!-- modal-body -->

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Düzenle Modalı Sonu -->
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('js')
<script>
    function toggleOldDeviceInput(userId) {
        let selectEl = document.getElementById('device_option_' + userId);
        let val = selectEl.value;
        let oldDevDiv = document.getElementById('oldDeviceDiv-' + userId);

        if (val === 'ban_kaldir_eski') {
            oldDevDiv.style.display = 'block';
        } else {
            oldDevDiv.style.display = 'none';
        }
    }
</script>
@endpush
