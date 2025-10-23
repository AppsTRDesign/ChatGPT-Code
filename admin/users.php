<?php
require __DIR__ . '/header.php';

use App\Helpers;

$csrfToken = Helpers::csrfToken();
?>
<h1 class="h3 mb-4">Üyeler</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table
            id="usersTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/users"
            data-search="true"
            data-pagination="true"
            data-page-list="[10, 25, 50, 100]"
            data-sort-name="created_at"
            data-sort-order="desc"
            data-response-handler="window.appHandlers.userResponseHandler"
            data-unique-id="id"
            data-mobile-responsive="true"
            data-locale="tr-TR"
            data-toolbar-align="left"
            data-buttons-align="right"
            data-csrf="<?= Helpers::e($csrfToken) ?>"
        >
            <thead>
                <tr>
                    <th data-field="id" data-sortable="true">ID</th>
                    <th data-field="username" data-sortable="true">Kullanıcı Adı</th>
                    <th data-field="email" data-sortable="true">E-posta</th>
                    <th data-field="role" data-formatter="window.appHandlers.roleFormatter" data-sortable="true">Rol</th>
                    <th data-field="created_at" data-sortable="true">Kayıt Tarihi</th>
                    <th data-field="id" data-formatter="window.appHandlers.userActionsFormatter" data-align="right">İşlemler</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
