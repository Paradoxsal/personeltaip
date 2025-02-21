@extends('layouts.user_type.auth')

@section('content')
    @if (session('loginsuccess'))
        <div class="m-3  alert alert-success alert-dismissible fade show" id="alert-success" role="alert">
            <span class="alert-text text-white">
                {{ session('success') }}
            </span>
            <span class="text-white">
                <strong>Başarıyla Giriş Yapıldı</strong>
            </span>
        </div>
    @endif

    <div class="row">
        <!-- 1) Toplam Konum -->
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Toplam Konum</p>
                                <h5 class="font-weight-bolder mb-0">
                                    {{ $toplamKonum ?? 0 }}
                                    
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="ni ni-map-big text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2) Toplam Kullanıcı Sayısı -->
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Toplam Kullanıcı Sayısı</p>
                                <h5 class="font-weight-bolder mb-0">
                                    {{ $toplamKullaniciSayisi ?? 0 }}
                                   
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="ni ni-world text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3) Toplam Birim Sayısı -->
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Toplam Birim Sayısı</p>
                                <h5 class="font-weight-bolder mb-0">
                                    {{ $toplamBirimSayisi ?? 0 }}
                                   
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="ni ni-building text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4) Yetkili Kişi Sayısı -->
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Yetkili Kişi Sayısı</p>
                                <h5 class="font-weight-bolder mb-0">
                                    {{ $yetkiliKisiSayisi ?? 0 }}
                              
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="ni ni-key-25 text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diğer kodlar (Projects tablosu, Personel Ekle formu, vb.) -->
    <div class="row my-4">
        <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
            <!-- ... Bu kısım aynı kalabilir ... -->

            <!-- tablo -->
            <div class="card">
                <div class="card-header pb-0">
                    <!-- ... -->
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Birimlerimiz</th>
                                    <th>Birim Başkanları</th>
                                    <th class="text-center">Birimdeki Personel Sayısı</th>
                                    <th class="text-center">Son Güncelleme</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($departments as $department)
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div>
                                                    <img src="../assets/img/small-logos/logo-atlassian.svg"
                                                         class="avatar avatar-sm me-3" alt="xd">
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $department->unit_name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="avatar-group mt-2">
                                                <span><b>{{ $department->unit_head }}</b></span>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="text-xs font-weight-bold">
                                                {{ $department->personel_sayisi }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="progress-wrapper w-75 mx-auto">
                                                <div class="progress-info">
                                                    <div class="progress-percentage">
                                                        <span class="text-xs font-weight-bold">
                                                            {{ $department->last_updated_at }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personel Ekle Kısmı -->
        <div class="col-lg-4 col-md-6">
            <div class="card z-index-0">
                <div class="card-header text-center pt-4">
                    <h5>Personel Ekle</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="mt-3 alert alert-primary alert-dismissible fade show" role="alert">
                            <span class="alert-text text-white">
                                {{ $errors->first() }}
                            </span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <i class="fa fa-close" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="m-3 alert alert-success alert-dismissible fade show" id="alert-success" role="alert">
                            <span class="alert-text text-white">
                                {{ session('success') }}
                            </span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <i class="fa fa-close" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('personel.create') }}">
                        @csrf
                        <div class="mb-3">
                            <input type="text" class="form-control" name="name" placeholder="Kullanıcı Adı"
                                   aria-label="Name" required>
                        </div>

                        <div class="mb-3">
                            <input type="password" class="form-control" name="password"
                                   placeholder="Şifreniz" aria-label="Password">
                        </div>

                        <div class="mt-4">
                            <select id="unit_id" name="unit_id" class="form-control" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->unit_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <br>
                        <div class="text-center">
                            <button type="submit" class="btn bg-gradient-primary btn-sm mb-0">
                                + Kullanıcı Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
