<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Получаем все работы портфолио, сгруппированные по категориям
$stmt = $pdo->prepare("
    SELECT * FROM portfolio 
    WHERE is_active = TRUE 
    ORDER BY category, sort_order ASC, created_at DESC
");
$stmt->execute();
$allWorks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группируем по категориям
$worksByCategory = [];
$categoryNames = [
    'lightshow' => 'Световые шоу',
    'anniversary' => 'Юбилеи', 
    'wedding' => 'Свадьбы',
    'concert' => 'Концерты',
    'kids' => 'Детям',
    'media' => 'Медиа',
    'corporate' => 'Компаниям'
];

foreach ($allWorks as $work) {
    $worksByCategory[$work['category']][] = $work;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Портфолио</h1>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Добавить изображение
        </a>
    </div>

    <!-- 📐 Информация об особых позициях -->
    <div class="alert alert-info mb-4">
        <h6>🎯 Особенности сетки портфолио:</h6>
        <ul class="mb-0">
            <li><strong>Позиции 4 и 7</strong> - имеют увеличенный размер (865×398px)</li>
            <li>Эти позиции <strong class="text-warning">нельзя изменять через перетаскивание</strong></li>
            <li>Для изменения используйте удаление и загрузку новых изображений</li>
        </ul>
    </div>

    <!-- Навигация по категориям -->
    <ul class="nav nav-tabs mb-4" id="portfolioTabs" role="tablist">
        <?php foreach ($categoryNames as $key => $name): 
            $count = isset($worksByCategory[$key]) ? count($worksByCategory[$key]) : 0;
        ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $key === 'lightshow' ? 'active' : '' ?>" 
                        id="<?= $key ?>-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#<?= $key ?>" 
                        type="button" 
                        role="tab">
                    <?= $name ?> 
                    <span class="badge bg-secondary"><?= $count ?></span>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Контент категорий -->
    <div class="tab-content" id="portfolioTabContent">
        <?php foreach ($categoryNames as $key => $name): ?>
            <div class="tab-pane fade <?= $key === 'lightshow' ? 'show active' : '' ?>" 
                 id="<?= $key ?>" 
                 role="tabpanel">
                 
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?= $name ?></h5>
                    <small class="text-muted">Перетаскивайте для изменения порядка</small>
                </div>

                <?php if (isset($worksByCategory[$key]) && !empty($worksByCategory[$key])): ?>
                    <div class="row portfolio-sortable" data-category="<?= $key ?>">
                        <?php foreach ($worksByCategory[$key] as $index => $work): 
                            $position = $index + 1;
                            $isSpecialPosition = $position === 4 || $position === 7;
                        ?>
                            <div class="col-md-3 mb-4 portfolio-item <?= $isSpecialPosition ? 'special-position' : '' ?>" 
                                 data-id="<?= $work['id'] ?>" 
                                 data-position="<?= $position ?>">
                                <div class="card position-relative">
                                    <!-- 🟧 Индикатор особой позиции -->
                                    <?php if ($isSpecialPosition): ?>
                                        <div class="special-badge">Особый размер</div>
                                    <?php endif; ?>
                                    
                                    <img src="/<?= htmlspecialchars($work['image_path']) ?>" 
                                         class="card-img-top" 
                                         alt="Портфолио"
                                         style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Поз. <?= $position ?>
                                            </small>
                                            <div>
                                                <a href="edit.php?id=<?= $work['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-work" 
                                                        data-id="<?= $work['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        В этой категории пока нет изображений. 
                        <a href="create.php?category=<?= $key ?>">Добавить первое изображение</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Подключаем SortableJS для перетаскивания -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Инициализация перетаскивания
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем Sortable для каждой категории
    document.querySelectorAll('.portfolio-sortable').forEach(container => {
        new Sortable(container, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            
            // 🚫 ЗАПРЕТ ПЕРЕТАСКИВАНИЯ НА ПОЗИЦИИ 4 И 7
            onMove: function(evt) {
                const dragged = evt.dragged;
                const related = evt.related;
                const willBeIndex = evt.draggedWithin === evt.to ? 
                    (evt.dragRect.top > evt.relatedRect.top ? evt.relatedIndex : evt.relatedIndex + 1) : 
                    evt.relatedIndex;
                
                // Запрещаем перемещение НА позиции 3 и 6 (4-й и 7-й элементы в 0-based индексации)
                if (willBeIndex === 3 || willBeIndex === 6) {
                    showPositionWarning();
                    dragged.style.cursor = 'not-allowed';
                    return false;
                }
                
                // Запрещаем перемещение С позиций 3 и 6 (4-й и 7-й элементы)
                const draggedPosition = parseInt(dragged.dataset.position);
                if (draggedPosition === 4 || draggedPosition === 7) {
                    showPositionWarning();
                    dragged.style.cursor = 'not-allowed';
                    return false;
                }
                
                dragged.style.cursor = 'move';
                return true;
            },
            
            onEnd: function(evt) {
                // Обновляем позиции всех элементов после перетаскивания
                updateItemPositions(evt.from);
                updatePortfolioOrder(evt.from.dataset.category);
            }
        });
    });

    // Обработчики удаления
    document.querySelectorAll('.delete-work').forEach(btn => {
        btn.addEventListener('click', function() {
            const workId = this.dataset.id;
            if (confirm('Удалить это изображение из портфолио?')) {
                deleteWork(workId);
            }
        });
    });
});

// Функция обновления позиций элементов
function updateItemPositions(container) {
    const items = container.querySelectorAll('.portfolio-item');
    items.forEach((item, index) => {
        const position = index + 1;
        item.dataset.position = position;
        
        // Обновляем отображаемую позицию
        const positionBadge = item.querySelector('.text-muted');
        if (positionBadge) {
            positionBadge.textContent = `Поз. ${position}`;
        }
        
        // Обновляем классы для особых позиций
        if (position === 4 || position === 7) {
            item.classList.add('special-position');
        } else {
            item.classList.remove('special-position');
        }
    });
}

// Функция обновления порядка в БД
function updatePortfolioOrder(category) {
    const items = document.querySelectorAll(`[data-category="${category}"] .portfolio-item`);
    const order = Array.from(items).map(item => item.dataset.id);
    
    fetch('update-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            category: category,
            order: order
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Порядок сохранен!', 'success');
        } else {
            showAlert('Ошибка сохранения порядка', 'error');
        }
    });
}

// Функция удаления работы
function deleteWork(workId) {
    fetch('delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: workId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Изображение удалено', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Ошибка удаления', 'error');
        }
    });
}

// 🚫 Функция показа предупреждения о запрете перетаскивания
function showPositionWarning() {
    // Удаляем старые предупреждения
    const oldWarnings = document.querySelectorAll('.position-warning');
    oldWarnings.forEach(warning => warning.remove());
    
    const warning = document.createElement('div');
    warning.className = 'alert alert-warning position-warning alert-dismissible fade show mt-3';
    warning.innerHTML = `
        <strong>⚠️ Внимание!</strong> Позиции 4 и 7 имеют особый размер (865×398px) и не могут быть изменены через перетаскивание.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').prepend(warning);
    
    // Автоматически скрываем через 4 секунды
    setTimeout(() => {
        if (warning.parentNode) {
            warning.remove();
        }
    }, 4000);
}

// Вспомогательная функция для уведомлений
function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').prepend(alert);
    
    setTimeout(() => alert.remove(), 3000);
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}
.sortable-chosen {
    transform: rotate(5deg);
}
.portfolio-item {
    cursor: move;
}

/* 🟧 Стили для особых позиций */
.special-position {
    position: relative;
}

.special-position .card {
    border: 2px solid #E75512 !important;
    background: linear-gradient(135deg, #fff9f7 0%, #fff 100%);
}

.special-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #E75512;
    color: white;
    padding: 4px 12px;
    font-size: 11px;
    border-radius: 12px;
    font-weight: 600;
    z-index: 10;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Курсор для запрещенных действий */
.portfolio-item[data-position="4"],
.portfolio-item[data-position="7"] {
    cursor: not-allowed !important;
}

/* Анимация для предупреждения */
.position-warning {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>