@extends('layouts.user_type.auth')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Kullanıcı Telefon Ayarları</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" id="usersTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Kullanıcı Adı
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Email
                                    </th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Telefon
                                    </th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Kayıt Tarihi
                                    </th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $user->email }}</p>
                                        </td>
                                        <td class="text-center">
                                            <p class="text-xs font-weight-bold mb-0">{{ $user->phone }}</p>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $user->created_at->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <button class="btn btn-link text-secondary mb-0" data-bs-toggle="modal"
                                                data-bs-target="#editUserModal-{{ $user->id }}">
                                                <i class="fas fa-cog text-sm"></i> Düzenle
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Düzenleme Modalı -->
                                    <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1"
                                         aria-labelledby="editUserModalLabel-{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editUserModalLabel-{{ $user->id }}">
                                                        {{ $user->name }} - Telefon Ayarları
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('phoneSettings.update', $user->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <!-- 1) device_info (mevcut değeri) -->
                                                        <div class="mb-3">
                                                            <label class="form-control-label">Cihaz Bilgisi (Mevcut)</label>
                                                            <input type="text" class="form-control" name="old_device_info"
                                                                   value="{{ $user->device_info }}"
                                                                   disabled>
                                                            <small class="text-muted">
                                                                user_devices tablosundaki mevcut bilgiyi de
                                                                senkron tutacağız.
                                                            </small>
                                                        </div>

                                                        <!-- 1.1) device_info Seçenek -->
                                                        <div class="mb-3">
                                                            <label class="form-control-label">
                                                                Cihaz Bilgisi Ne Yapılsın?
                                                            </label>
                                                            <select name="device_info_option" class="form-control" id="deviceInfoSelect-{{ $user->id }}"
                                                                    onchange="toggleNewDeviceInput({{ $user->id }})">
                                                                <option value="1" selected>Mevcut cihaz kalsın</option>
                                                                <option value="2">Cihaz bilgisi kaldır</option>
                                                                <option value="3">Yeni cihaz ekle</option>
                                                            </select>
                                                        </div>

                                                        <!-- 1.2) Yeni cihaz ekleme (gizli) -->
                                                        <div class="mb-3" id="newDeviceInfoWrapper-{{ $user->id }}" style="display: none;">
                                                            <label class="form-control-label">Yeni Cihaz Bilgisi</label>
                                                            <input type="text" class="form-control" name="new_device_info">
                                                        </div>

                                                        <!-- 2) cihaz_yetki -->
                                                        @php
                                                            // 0 veya 1
                                                            $yetkiDurumu = ($user->device_yetki == 1) ? 'ver' : 'kaldır';
                                                        @endphp

                                                        <div class="mb-3">
                                                            <label class="form-control-label">Cihaz Yetkisi</label>
                                                            <select name="device_yetki_option" class="form-control">
                                                                <option value="ver" {{ $user->device_yetki == 1 ? 'selected' : '' }}>
                                                                    Cihaz Yetki Ver
                                                                </option>
                                                                <option value="kaldir" {{ $user->device_yetki == 0 ? 'selected' : '' }}>
                                                                    Cihaz Yetki Kaldır
                                                                </option>
                                                            </select>
                                                            <small class="text-muted">
                                                                1 => Yetkili, 0 => Yetkisiz
                                                            </small>
                                                        </div>

                                                        <!-- (Aynı işlemler user_devices tablosunda da yapılacak) -->

                                                        <hr>

                                                        <!-- 3) personal_access_tokens => token göster -->
                                                        <div class="mb-3">
                                                            <label class="form-control-label">Mevcut Token Verisi</label>
                                                            <input type="text" class="form-control" name="old_token"
                                                                value="{{ $user->token_string ?? 'Yok' }}" disabled>
                                                            <small class="text-muted">
                                                                Bu token personal_access_tokens tablosundan geliyor.
                                                            </small>
                                                        </div>

                                                        <!-- 3.1) Giriş Süresi Ayarları -->
                                                        <div class="mb-3">
                                                            <label class="form-control-label">
                                                                Giriş Süresi Ayarları
                                                            </label>
                                                            <select name="token_option" class="form-control">
                                                                <option value="sabit" selected>Giriş süresi sabit</option>
                                                                <option value="sifirla">Giriş süresini sıfırla</option>
                                                            </select>
                                                            <small class="text-muted">
                                                                Sıfırla seçilirse random 10 haneli token üretilir.
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                            Kapat
                                                        </button>
                                                        <button type="submit" class="btn btn-primary">
                                                            Güncelle
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Düzenleme Modal -->
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript: yeni cihaz ekleme input'unu açıp kapatma -->
<script>
function toggleNewDeviceInput(userId) {
    var selectEl = document.getElementById("deviceInfoSelect-" + userId);
    var wrapperEl = document.getElementById("newDeviceInfoWrapper-" + userId);

    if (selectEl.value === "3") {
        // "yeni cihaz ekle"
        wrapperEl.style.display = "block";
    } else {
        wrapperEl.style.display = "none";
    }
}
</script>
@endsection
