<?php
session_start();
require_once '../includes/db.php';

$base_path = '/admin/';

// Получаем список работ
try {
    $stmt = $pdo->query("
        SELECT w.*, 
               COUNT(wi.id) as images_count
        FROM works w
        LEFT JOIN work_images wi ON w.id = wi.work_id
        GROUP BY w.id
        ORDER BY w.created_at DESC
    ");
    $works = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Ошибка загрузки работ: " . $e->getMessage();
    $works = [];
}
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Управление работами</h1>
        <a href="<?= $base_path ?>works/create.php" class="btn btn-primary btn-lg">
            <i class="bi bi-plus-circle"></i> Добавить работу
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($works)): ?>
        <div class="alert alert-info text-center">
            <h4>Работ пока нет</h4>
            <p>Добавьте первую работу, чтобы она отобразилась здесь</p>
            <a href="<?= $base_path ?>works/create.php" class="btn btn-primary">Добавить первую работу</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Год</th>
                        <th>Изображения</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($works as $work): ?>
                    <tr>
                        <td><?= htmlspecialchars($work['id']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($work['title']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($work['description']) ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($work['year']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $work['images_count'] ?> изображ.</span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= date('d.m.Y H:i', strtotime($work['created_at'])) ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $base_path ?>works/edit.php?id=<?= $work['id'] ?>" 
                                   class="btn btn-outline-primary" 
                                   title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="#" 
                                   class="btn btn-outline-danger delete-btn" 
                                   title="Удалить"
                                   data-id="<?= $work['id'] ?>"
                                   data-title="<?= htmlspecialchars($work['title']) ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-warning">
            <small>
                <i class="bi bi-info-circle"></i> 
                Максимальное количество работ: 20. В каждой работе можно загрузить от 3 до 9 изображений.
            </small>
        </div>
    <?php endif; ?>
</div>

<script>
// Подтверждение удаления
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const workId = this.getAttribute('data-id');
            const workTitle = this.getAttribute('data-title');
            
            const confirmDelete = confirm('Вы уверены, что хотите удалить работу "' + workTitle + '"?');
            
            if (confirmDelete) {
                window.location.href = 'delete.php?id=' + workId;
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>