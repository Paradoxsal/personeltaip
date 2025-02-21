@extends('layouts.app') 
{{-- Projenizde hangi layout varsa: extends('layouts.user_type.auth') veya benzerini kullanabilirsiniz --}}

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
    @endif
    @if(session('info'))
      <div class="alert alert-info">
          {{ session('info') }}
      </div>
    @endif

    <h3>Kullanıcı Yönetimi</h3>

    <table class="table table-bordered align-items-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Email</th>
                <th>Ban Durumu</th>
                <th>Cihaz Yetkisi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->banned)
                        <span class="text-danger">Banlı</span>
                        @if($user->banned_log)
                            <br>
                            <small>{{ $user->banned_log }}</small>
                        @endif
                    @else
                        <span class="text-success">Aktif</span>
                    @endif
                </td>
                <td>
                    @if($user->cihaz_yetki == 1)
                        <span class="badge bg-success">Yetkili</span>
                    @else
                        <span class="badge bg-secondary">Yetkisiz</span>
                    @endif
                </td>
                <td>
                    <!-- DÜZENLE (modal açar) -->
                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editModal-{{ $user->id }}">
                        Düzenle
                    </button>

                    {{-- BAN / UNBAN --}}
                    @if($user->banned)
                        <!-- Eğer banlı ise "Ban Kaldır" butonu -->
                        <form action="{{ route('users.manage') }}" method="POST" style="display:inline-block;">
                            @csrf
                            <input type="hidden" name="action" value="unban">
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-sm btn-warning">
                                Ban Kaldır
                            </button>
                        </form>
                    @else
                        <!-- Banlı değilse "Banla" butonu -->
                        <form action="{{ route('users.manage') }}" method="POST" style="display:inline-block;">
                            @csrf
                            <input type="hidden" name="action" value="ban">
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-sm btn-danger">
                                Banla
                            </button>
                        </form>
                    @endif

                    <!-- Cihaz Bilgilerini Sıfırla -->
                    <form action="{{ route('users.manage') }}" method="POST" style="display:inline-block;">
                        @csrf
                        <input type="hidden" name="action" value="reset-device">
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <button type="submit" class="btn btn-sm btn-secondary">
                            Cihaz Sıfırla
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Düzenleme Modalı -->
            <div class="modal fade" id="editModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('users.manage') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Kullanıcı Düzenle (ID: {{ $user->id }})
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name_{{ $user->id }}" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" name="name"
                                           id="name_{{ $user->id }}" 
                                           value="{{ $user->name }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email_{{ $user->id }}" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email"
                                           id="email_{{ $user->id }}"
                                           value="{{ $user->email }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_{{ $user->id }}" class="form-label">Yeni Şifre</label>
                                    <input type="password" class="form-control" name="password"
                                           id="password_{{ $user->id }}"
                                           placeholder="Değiştirmek istemiyorsanız boş bırakın">
                                </div>
                                <!-- Diğer alanlar varsa buraya ekleyin -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Kapat</button>
                                <button type="submit" class="btn btn-primary">
                                    Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Düzenleme Modalı Sonu -->

        @endforeach
        </tbody>
    </table>
</div>
@endsection
