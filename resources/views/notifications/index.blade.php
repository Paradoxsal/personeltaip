@extends('layouts.user_type.auth')

@section('content')
    <div class="container mt-4">
        <h3>Bildirimler</h3>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- YENİ BİLDİRİM OLUŞTUR -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
            Yeni Bildirim Oluştur
        </button>

        <!-- Tabloda mevcut bildirimlerin listesi -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tip (action)</th>
                    <th>Başlık</th>
                    <th>Hedef Türü</th>
                    <th>user_id</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notifications as $n)
                    <tr>
                        <td>{{ $n->id }}</td>
                        <td>{{ $n->action }}</td>
                        <td>{{ $n->title }}</td>
                        <td>{{ $n->target_type }}</td>
                        <td>{{ $n->user_id }}</td>
                        <td>{{ $n->status }}</td>
                        <td>
                            <!-- Edit button (modal açabilir) -->
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                data-bs-target="#editModal-{{ $n->id }}">Düzenle</button>

                            <!-- Silme formu -->
                            <form action="{{ route('notifications.destroy', $n->id) }}" method="POST"
                                style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"
                                    onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal-{{ $n->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('notifications.update', $n->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="modal-header">
                                        <h5 class="modal-title">Bildirim Düzenle: #{{ $n->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Bildirim Tipi (action) -->
                                        <div class="mb-3">
                                            <label>Bildirim Tipi (action)</label>
                                            <select name="action" class="form-select"
                                                id="editActionSelect-{{ $n->id }}"
                                                onchange="toggleEditFields({{ $n->id }})">
                                                <option value="push" {{ $n->action == 'push' ? 'selected' : '' }}>Normal Bildirim</option>
                                                <option value="data" {{ $n->action == 'data' ? 'selected' : '' }}>Konum Bildirimi</option>
                                                <option value="resume" {{ $n->action == 'resume' ? 'selected' : '' }}>WorkManager Başlat</option>
                                                <option value="pause" {{ $n->action == 'pause' ? 'selected' : '' }}>WorkManager Durdur</option>
                                            </select>
                                        </div>

                                        <!-- Başlık/Body -->
                                        <div class="mb-3" id="editTitleDiv-{{ $n->id }}">
                                            <label>Başlık</label>
                                            <input type="text" name="title" class="form-control"
                                                value="{{ $n->title }}">
                                        </div>

                                        <div class="mb-3" id="editBodyDiv-{{ $n->id }}">
                                            <label>İçerik (body)</label>
                                            <textarea name="body" class="form-control">{{ $n->body }}</textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label>Planlanan Tarih</label>
                                            <input type="datetime-local" name="scheduled_at" class="form-control"
                                                value="{{ $n->scheduled_at ? \Carbon\Carbon::parse($n->scheduled_at)->format('Y-m-d\\TH:i') : '' }}">
                                        </div>

                                        <div class="mb-3">
                                            <label>Hedef Türü</label>
                                            <select name="target_type" class="form-select"
                                                id="editTargetTypeSelect-{{ $n->id }}"
                                                onchange="toggleEditTargetFields({{ $n->id }})">
                                                <option value="all" {{ $n->target_type == 'all' ? 'selected' : '' }}>Tüm Kullanıcılar</option>
                                                <option value="user" {{ $n->target_type == 'user' ? 'selected' : '' }}>Tek Kullanıcı</option>
                                                <option value="group" {{ $n->target_type == 'group' ? 'selected' : '' }}>Birim</option>
                                            </select>
                                        </div>

                                        <!-- Tek kullanıcı -->
                                        <div id="editSingleUserDiv-{{ $n->id }}" class="mb-3" style="display:none;">
                                            <label>Tek Kullanıcı Seç</label>
                                            <select name="selected_user_id" class="form-select">
                                                <option value="">--Seç--</option>
                                                @foreach ($allUsers as $u)
                                                    <option value="{{ $u->id }}"
                                                        @if ($n->target_type == 'user' && $n->user_id == $u->id) selected @endif>
                                                        {{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Birim Seç -->
                                        <div id="editGroupDiv-{{ $n->id }}" class="mb-3" style="display:none;">
                                            <label>Birim Seç</label>
                                            <select name="selected_unit_id" class="form-select">
                                                <option value="">--Seç--</option>
                                                @foreach ($allUnits as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Güncelleme sonrasında, bu birime ait user_id’ler virgülle kaydedilir.</small>
                                        </div>

                                    </div> <!-- modal-body -->

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                        <button type="submit" class="btn btn-primary">Kaydet</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>

    </div>


    <!-- Modal: Yeni Bildirim Ekle (store) -->
    <div class="modal fade" id="createNotificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('notifications.store') }}" method="POST">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Bildirim Oluştur</h5>
                        <button type="button" class="btn btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- Bildirim Tipi (action) -->
                        <div class="mb-3">
                            <label>Bildirim Tipi (action)</label>
                            <select name="action" class="form-select" id="createActionSelect"
                                onchange="toggleCreateActionFields()">
                                <option value="push">Normal Bildirim</option>
                                <option value="data">Konum Bildirimi</option>
                                <option value="resume">WorkManager Başlat</option>
                                <option value="pause">WorkManager Durdur</option>
                            </select>
                        </div>

                        <!-- title / body -->
                        <div class="mb-3" id="createTitleDiv">
                            <label>Başlık</label>
                            <input type="text" name="title" class="form-control">
                        </div>

                        <div class="mb-3" id="createBodyDiv">
                            <label>İçerik (body)</label>
                            <textarea name="body" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Planlanan Tarih</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Hedef Türü</label>
                            <select name="target_type" class="form-select" id="createTargetTypeSelect"
                                onchange="toggleCreateTargetFields()">
                                <option value="all">Tüm Kullanıcılar</option>
                                <option value="user">Tek Kullanıcı</option>
                                <option value="group">Birim</option>
                            </select>
                        </div>

                        <!-- Tek kullanıcı -->
                        <div id="createSingleUserDiv" class="mb-3" style="display:none;">
                            <label>Tek Kullanıcı Seç</label>
                            <select name="selected_user_id" class="form-select">
                                <option value="">--Seç--</option>
                                @foreach ($allUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Birim Seçimi -->
                        <div id="createGroupDiv" class="mb-3" style="display:none;">
                            <label>Birim Seç</label>
                            <select name="selected_unit_id" class="form-select">
                                <option value="">--Seç--</option>
                                @foreach ($allUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Bu birimdeki kullanıcıların ID’leri virgül ile kaydedilir.</small>
                        </div>

                    </div> <!-- modal-body -->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-primary">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    // CREATE => action seçimine göre title/body alanlarını göster/gizle
    function toggleCreateActionFields() {
        let val = document.getElementById('createActionSelect').value;
        let titleDiv = document.getElementById('createTitleDiv');
        let bodyDiv  = document.getElementById('createBodyDiv');

        if (val === 'push') {
            titleDiv.style.display = 'block';
            bodyDiv.style.display  = 'block';
        } else {
            // data, resume veya pause komutlarında title/body gizlenecek
            titleDiv.style.display = 'none';
            bodyDiv.style.display  = 'none';
        }
    }

    // CREATE => target_type alanına göre tek kullanıcı / grup seçimi
    function toggleCreateTargetFields() {
        let val = document.getElementById('createTargetTypeSelect').value;
        let singleDiv = document.getElementById('createSingleUserDiv');
        let groupDiv  = document.getElementById('createGroupDiv');

        singleDiv.style.display = 'none';
        groupDiv.style.display  = 'none';

        if (val === 'user') {
            singleDiv.style.display = 'block';
        } else if (val === 'group') {
            groupDiv.style.display = 'block';
        }
    }

    // EDIT => action seçimine göre title/body alanlarını göster/gizle
    function toggleEditFields(notifId) {
        let actionVal = document.getElementById('editActionSelect-' + notifId).value;
        let titleDiv  = document.getElementById('editTitleDiv-' + notifId);
        let bodyDiv   = document.getElementById('editBodyDiv-' + notifId);

        if (actionVal === 'push') {
            titleDiv.style.display = 'block';
            bodyDiv.style.display  = 'block';
        } else {
            // data, resume veya pause durumunda title/body gizlenecek
            titleDiv.style.display = 'none';
            bodyDiv.style.display  = 'none';
        }
        // Hedef türü alanını da ayarla
        toggleEditTargetFields(notifId);
    }

    // EDIT => target_type alanına göre tek kullanıcı / grup seçimi
    function toggleEditTargetFields(notifId) {
        let val = document.getElementById('editTargetTypeSelect-' + notifId).value;
        let singleDiv = document.getElementById('editSingleUserDiv-' + notifId);
        let groupDiv  = document.getElementById('editGroupDiv-' + notifId);

        singleDiv.style.display = 'none';
        groupDiv.style.display  = 'none';

        if (val === 'user') {
            singleDiv.style.display = 'block';
        } else if (val === 'group') {
            groupDiv.style.display = 'block';
        }
    }

    // Sayfa yüklendiğinde create modalindeki action alanını kontrol et
    document.addEventListener('DOMContentLoaded', function() {
        toggleCreateActionFields();
    });
</script>
@endpush
