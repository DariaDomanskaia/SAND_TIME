<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->query("SELECT * FROM services ORDER BY created_at DESC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Управление услугами</h1>
    <a href="create.php" class="btn btn-primary btn-lg">
        <i class="bi bi-plus-circle"></i> Добавить услугу
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($services)): ?>
    <div class="alert alert-info text-center">
        <h4>Услуг пока нет</h4>
        <p>Добавьте первую услугу, чтобы она отобразилась здесь</p>
        <a href="create.php" class="btn btn-primary">Добавить первую услугу</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= htmlspecialchars($service['id']) ?></td>
                    <td>
                        <?php if ($service['image_path']): ?>
                        <img src="../<?= htmlspecialchars($service['image_path']) ?>" 
                             alt="Изображение услуги" class="img-thumbnail" width="80" height="60" style="object-fit: cover;">
                        <?php else: ?>
                        <span class="text-muted">Нет изображения</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($service['title']) ?></strong>
                    </td>
                    <td><?= htmlspecialchars($service['description']) ?></td>
                    <td>
                        <small class="text-muted">
                            <?= date('d.m.Y H:i', strtotime($service['created_at'])) ?>
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="edit.php?id=<?= $service['id'] ?>" 
                               class="btn btn-outline-primary" 
                               title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="#" 
                               class="btn btn-outline-danger delete-btn" 
                               title="Удалить"
                               data-id="<?= $service['id'] ?>"
                               data-title="<?= htmlspecialchars($service['title']) ?>"
                               data-csrf="<?= $_SESSION['csrf_token'] ?>">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
// Подтверждение удаления
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const serviceId = this.getAttribute('data-id');
            const serviceTitle = this.getAttribute('data-title');
            const csrfToken = this.getAttribute('data-csrf');
            
            const confirmDelete = confirm('Вы уверены, что хотите удалить услугу "' + serviceTitle + '"?');
            
            if (confirmDelete) {
                window.location.href = 'delete.php?id=' + serviceId + '&csrf_token=' + csrfToken;
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>