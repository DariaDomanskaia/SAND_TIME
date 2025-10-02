<?php
session_start();
require_once '../includes/db.php';

// Получаем список работ
$stmt = $pdo->query("SELECT w.*, COUNT(wi.id) as image_count 
                     FROM works w 
                     LEFT JOIN work_images wi ON w.id = wi.work_id 
                     GROUP BY w.id 
                     ORDER BY w.year DESC, w.created_at DESC");
$works = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-images"></i> Управление работами</h1>
    <a href="create.php" class="btn btn-primary btn-lg">
        <i class="bi bi-plus-circle"></i> Добавить работу
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($works)): ?>
    <div class="alert alert-info text-center">
        <h4>Работ пока нет</h4>
        <p>Добавьте первую работу для отображения в аккордеоне</p>
        <a href="create.php" class="btn btn-primary">Добавить первую работу</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Описание</th>
                    <th>Год</th>
                    <th>Изображений</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($works as $work): ?>
                <tr>
                    <td><?= $work['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($work['title']) ?></strong>
                    </td>
                    <td><?= htmlspecialchars($work['description']) ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= $work['year'] ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $work['image_count'] >= 3 ? 'success' : 'warning' ?>">
                            <?= $work['image_count'] ?> / 9
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="edit.php?id=<?= $work['id'] ?>" 
                               class="btn btn-outline-primary" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="#" 
                               class="btn btn-outline-danger delete-btn" 
                               title="Удалить"
                               data-id="<?= $work['id'] ?>"
                               data-title="<?= htmlspecialchars($work['title']) ?>"
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
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const workId = this.getAttribute('data-id');
            const workTitle = this.getAttribute('data-title');
            const csrfToken = this.getAttribute('data-csrf');
            
            const confirmDelete = confirm('Вы уверены, что хотите удалить работу "' + workTitle + '"?');
            
            if (confirmDelete) {
                window.location.href = 'delete.php?id=' + workId + '&csrf_token=' + csrfToken;
            }
        });
    });
});

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">Статистика работ</h5>
                <p class="card-text">
                    <strong>Всего работ:</strong> <?= count($works) ?><br>
                    <strong>Лимит:</strong> 20 работ<br>
                    <strong>Осталось:</strong> <?= max(0, 20 - count($works)) ?>
                </p>
                <?php if (count($works) >= 20): ?>
                    <div class="alert alert-warning mb-0">
                        <small>Достигнут лимит работ. Удалите некоторые работы для добавления новых.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</script>

<?php include '../includes/footer.php'; ?>