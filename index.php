<?php require_once __DIR__ . '/header.php'; ?>
  <div id="auth-overlay" class="auth-overlay">
    <div class="auth-card">
      <h2>Авторизация</h2>
      <p class="text-muted">Введите пароль для входа</p>
      <form id="auth-form" class="flex-col">
        <input type="password" id="auth-password" placeholder="Пароль" required autofocus />
        <button type="submit" class="btn-primary">Войти</button>
      </form>
      <div id="auth-error" class="form-error"></div>
    </div>
  </div>
  <main class="hidden">
    <!-- Дашборд -->
    <section id="dashboard" class="active">
      <div class="card">
        <h2>Состояние производства</h2>
        <div class="stats" id="dashboard-stats"></div>
      </div>
      <div class="card">
        <h3>Последние технологические карты</h3>
        <div id="dashboard-cards"></div>
      </div>
    </section>

    <!-- Тех. карты -->
    <section id="cards">
      <div class="card">
        <h2>Технологические карты</h2>
        <div class="cards-subtabs">
          <button class="subtab-btn active" data-cards-tab="list">Карты</button>
          <button class="subtab-btn" data-cards-tab="directory">Операции и участки</button>
        </div>

        <div id="cards-list-panel" class="cards-tab-panel">
          <div class="flex" style="margin-bottom:8px; align-items:flex-end; flex-wrap:wrap; gap:8px;">
            <div class="flex-col" style="flex:1 1 260px;">
              <label for="cards-search">Поиск техкарты (№ карты, название, заказ)</label>
              <input id="cards-search" placeholder="Введите № EAN-13, название или номер заказа" />
            </div>
            <div class="flex-col" style="flex:0 0 auto;">
              <button class="btn-secondary" id="cards-search-clear">Сбросить</button>
            </div>
          </div>
          <div class="cards-toolbar">
            <div class="button-group">
              <button class="btn-primary" id="btn-new-card">Создать карту</button>
            </div>
          </div>
          <div id="cards-table-wrapper"></div>
        </div>

        <div id="cards-directory-panel" class="cards-tab-panel hidden">
          <div class="directory-grid inset-card">
            <div class="directory-panel">
              <h3>Справочник участков</h3>
              <form id="center-form" class="flex" style="flex-wrap:wrap;">
                <div class="flex-col" style="flex:1 1 200px;">
                  <label for="center-name">Название участка</label>
                  <input id="center-name" required />
                </div>
                <div class="flex-col" style="flex:2 1 260px;">
                  <label for="center-desc">Описание</label>
                  <input id="center-desc" />
                </div>
                <div class="flex-col" style="flex:0 0 auto; align-self:flex-end;">
                  <button type="submit" class="btn-primary">Добавить участок</button>
                </div>
              </form>
              <div id="centers-table-wrapper"></div>
            </div>

            <div class="directory-panel">
              <h3>Справочник операций</h3>
              <form id="op-form" class="flex" style="flex-wrap:wrap;">
                <div class="flex-col" style="flex:0 1 160px;">
                  <label for="op-code">Код операции</label>
                  <input id="op-code" placeholder="Напр. OP-1234" />
                </div>
                <div class="flex-col" style="flex:1 1 200px;">
                  <label for="op-name">Название операции</label>
                  <input id="op-name" required />
                </div>
                <div class="flex-col" style="flex:2 1 260px;">
                  <label for="op-desc">Описание</label>
                  <input id="op-desc" />
                </div>
                <div class="flex-col" style="flex:0 1 140px;">
                  <label for="op-time">Рекомендуемое время (мин)</label>
                  <input id="op-time" type="number" min="1" value="30" />
                </div>
                <div class="flex-col" style="flex:0 0 auto; align-self:flex-end;">
                  <button type="submit" class="btn-primary">Добавить операцию</button>
                </div>
              </form>
              <div id="ops-table-wrapper"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Трекер -->
    <section id="workorders">
      <div class="card">
        <h2>Трекер</h2>

        <!-- Поиск -->
        <div class="flex" style="margin-bottom:8px; align-items:flex-end;">
          <div class="flex-col" style="flex:1 1 260px;">
            <label for="workorder-search">Поиск техкарты (№ карты, название, заказ)</label>
            <input id="workorder-search" placeholder="Введите № EAN-13, название или номер заказа" />
          </div>
          <div class="flex-col" style="flex:0 1 200px;">
            <label for="workorder-status">Статус карты</label>
            <select id="workorder-status">
              <option value="ALL">Все</option>
              <option value="NOT_STARTED">Не запущена</option>
              <option value="IN_PROGRESS">Выполняется</option>
              <option value="PAUSED">Пауза</option>
              <option value="MIXED">Смешанно</option>
              <option value="DONE">Выполнено</option>
            </select>
          </div>
          <div class="flex-col" style="flex:0 0 auto;">
            <button class="btn-secondary" id="workorder-search-clear">Сбросить</button>
          </div>
        </div>

        <p style="font-size:12px; color:#6b7280; margin-top:0;">
          Исполнитель выбирает нужную операцию и нажимает <strong>«Начать»</strong>, при необходимости <strong>«Пауза»</strong> / <strong>«Продолжить»</strong>, после завершения — <strong>«Завершить»</strong>.
        </p>
        <div id="workorders-table-wrapper"></div>
      </div>
    </section>

    <!-- Архив -->
    <section id="archive">
      <div class="card">
        <h2>Архив маршрутных карт</h2>
        <div class="flex" style="margin-bottom:8px; align-items:flex-end;">
          <div class="flex-col" style="flex:1 1 260px;">
            <label for="archive-search">Поиск в архиве (№ карты, название, заказ)</label>
            <input id="archive-search" placeholder="Введите № EAN-13, название или номер заказа" />
          </div>
          <div class="flex-col" style="flex:0 1 200px;">
            <label for="archive-status">Статус карты</label>
            <select id="archive-status">
              <option value="ALL">Все</option>
              <option value="NOT_STARTED">Не запущена</option>
              <option value="IN_PROGRESS">Выполняется</option>
              <option value="PAUSED">Пауза</option>
              <option value="MIXED">Смешанно</option>
              <option value="DONE">Выполнено</option>
            </select>
          </div>
          <div class="flex-col" style="flex:0 0 auto;">
            <button class="btn-secondary" id="archive-search-clear">Сбросить</button>
          </div>
        </div>
        <p style="font-size:12px; color:#6b7280; margin-top:0;">
          Архивные карты доступны только для просмотра. При необходимости нажмите «Повторить», чтобы создать копию и перезапустить процесс.
        </p>
        <div id="archive-table-wrapper"></div>
      </div>
    </section>

    <!-- Пользователи -->
    <section id="users">
      <div class="card">
        <h2>Пользователи</h2>
        <div class="flex" style="justify-content:space-between; align-items:center; margin-bottom:12px;">
          <p class="text-muted" style="margin:0;">Управление доступом. Пароли должны быть уникальными (буквы+цифры, ≥6 символов).</p>
          <button id="btn-new-user" class="btn-primary">Создать пользователя</button>
        </div>
        <div id="users-table"></div>
      </div>

      <div class="modal hidden" id="user-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
          <div class="modal-header">
            <h3 id="user-modal-title">Пользователь</h3>
          </div>
          <div class="modal-body">
            <form id="user-form" class="flex-col">
              <input type="hidden" id="user-id" />
              <label>Имя пользователя
                <input id="user-name-input" required />
              </label>
              <label>Пароль
                <div class="flex" style="align-items:flex-end; gap:8px;">
                  <input id="user-password" placeholder="Буквы+цифры, ≥6" />
                  <button type="button" id="btn-gen-pass" class="btn-secondary btn-small">Сгенерировать</button>
                  <button type="button" id="btn-pass-barcode" class="btn-secondary btn-small">Печать Штрихкода</button>
                </div>
              </label>
              <label>Уровень доступа
                <select id="user-level"></select>
              </label>
              <label class="flex" style="align-items:center; gap:8px;">
                <input type="checkbox" id="user-active" checked /> Активен
              </label>
              <div id="user-error" class="form-error"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" id="user-cancel">Отмена</button>
            <button type="button" class="btn-primary" id="user-save">Сохранить</button>
          </div>
        </div>
      </div>

      <div class="modal hidden" id="user-barcode-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Пароль в формате Code-128</h3>
          </div>
          <div class="modal-body">
            <div id="user-barcode"></div>
            <p id="user-barcode-name"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" id="user-barcode-close">Закрыть</button>
            <button type="button" class="btn-primary" id="user-barcode-print">Печать</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Уровни доступа -->
    <section id="access">
      <div class="card">
        <h2>Уровни доступа</h2>
        <div class="flex" style="justify-content:space-between; align-items:center; margin-bottom:12px;">
          <p class="text-muted" style="margin:0;">Гибкая настройка прав просмотра/изменения по разделам.</p>
          <button id="btn-new-level" class="btn-primary">Создать уровень</button>
        </div>
        <div id="levels-table"></div>
      </div>

      <div class="modal hidden" id="level-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
          <div class="modal-header">
            <h3 id="level-modal-title">Уровень доступа</h3>
          </div>
          <div class="modal-body">
            <form id="level-form" class="flex-col">
              <input type="hidden" id="level-id" />
              <label>Название уровня
                <input id="level-name" required />
              </label>
              <label>Описание
                <input id="level-desc" />
              </label>
              <div class="flex" style="gap:12px;">
                <div class="flex-col" style="flex:1 1 200px;">
                  <label>Стартовая вкладка</label>
                  <select id="level-default-tab">
                    <option value="dashboard">Дашборд</option>
                    <option value="cards">Тех. карты</option>
                    <option value="workorders">Трекер</option>
                  </select>
                </div>
                <div class="flex-col" style="flex:1 1 200px;">
                  <label>Авто-выход (мин)</label>
                  <input type="number" id="level-timeout" min="1" value="30" />
                </div>
              </div>
              <div id="level-perms"></div>
              <div id="level-error" class="form-error"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" id="level-cancel">Отмена</button>
            <button type="button" class="btn-primary" id="level-save">Сохранить</button>
          </div>
        </div>
      </div>
    </section>

  <div id="card-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="card-modal-title">
    <div class="modal-content card-modal-content">
      <div class="modal-header">
        <h2 id="card-modal-title">Карта</h2>
      </div>
      <div class="modal-body">
        <form id="card-form" class="flex-col">
          <input type="hidden" id="card-id" />
          <div class="card-meta-grid">
            <div class="flex-col">
              <label for="card-name">Наименование карты / изделия</label>
              <input id="card-name" required />
            </div>
            <div class="flex-col">
              <label for="card-qty">Количество изделий, шт</label>
              <input id="card-qty" type="number" min="0" step="1" />
            </div>
            <div class="flex-col">
              <label for="card-order">Номер / код заказа</label>
              <input id="card-order" />
            </div>
            <div class="flex-col">
              <label for="card-drawing">Чертёж / обозначение детали</label>
              <input id="card-drawing" />
            </div>
            <div class="flex-col">
              <label for="card-material">Материал</label>
              <input id="card-material" />
            </div>
          </div>
          <div class="flex-col">
            <label for="card-desc">Описание</label>
            <textarea id="card-desc"></textarea>
          </div>
          <div class="flex" style="align-items:center; justify-content:space-between;">
            <div>
              <strong>Статус:</strong> <span id="card-status-text"></span>
            </div>
            <div class="flex" style="gap:8px; align-items:center;">
              <button type="button" id="card-attachments-btn" class="btn-secondary">📎 Файлы (0)</button>
            </div>
          </div>
        </form>

        <div class="card inset-card" id="route-editor">
          <h3>Маршрут выполнения операций</h3>
          <div id="route-table-wrapper"></div>
          <h3>Добавить операцию в маршрут</h3>
          <form id="route-form" class="flex route-form-grid" style="flex-wrap:wrap;">
            <div class="flex-col" style="flex:1 1 140px;">
              <label for="route-op-code-filter">Поиск по коду</label>
              <input id="route-op-code-filter" placeholder="Напишите код" />
            </div>
            <div class="flex-col" style="flex:2 1 180px;">
              <label for="route-op">Операция</label>
              <select id="route-op" required></select>
            </div>
            <div class="flex-col" style="flex:2 1 180px;">
              <label for="route-center">Участок</label>
              <select id="route-center" required></select>
            </div>
            <div class="flex-col" style="flex:1 1 120px;">
              <label for="route-executor">Исполнитель</label>
              <input id="route-executor" placeholder="ФИО" />
            </div>
            <div class="flex-col" style="flex:1 1 120px;">
              <label for="route-planned">Плановое время (мин)</label>
              <input id="route-planned" type="number" min="1" value="30" required />
            </div>
            <div class="flex-col" style="flex:1 1 120px;">
              <label for="route-order">Очередность</label>
              <input id="route-order" type="number" min="1" value="1" />
            </div>
            <div class="flex-col" style="flex:1 1 120px;">
              <label for="route-notes">Комментарий</label>
              <input id="route-notes" />
            </div>
            <div class="flex-col" style="flex:0 0 auto; align-self:flex-end;">
              <button type="submit" class="btn-primary">Добавить</button>
            </div>
          </form>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" id="card-print-btn" class="btn-secondary">Печать</button>
        <button type="button" id="card-save-btn" class="btn-primary">Сохранить карту</button>
        <button type="button" id="card-cancel-btn" class="btn-secondary">Закрыть</button>
      </div>
    </div>
  </div>

  <footer>
    Локальное веб-приложение. Данные сохраняются на сервере и доступны из нескольких браузеров.
  </footer>

  <div id="attachments-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="attachments-title">
    <div class="modal-content attachments-content">
      <div class="modal-header">
        <h3 id="attachments-title">Файлы карты</h3>
      </div>
      <div class="modal-body">
        <div class="attachments-actions">
          <button type="button" id="attachments-add-btn" class="btn-primary">Добавить файл</button>
          <input type="file" id="attachments-input" class="hidden-input" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,application/zip,application/x-rar-compressed,application/x-7z-compressed" multiple />
          <span id="attachments-upload-hint" class="upload-hint"></span>
        </div>
        <div id="attachments-list"></div>
      </div>
      <div class="modal-actions">
        <button type="button" id="attachments-close" class="btn-secondary">Закрыть</button>
      </div>
    </div>
  </div>

  <div id="log-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="log-title">
    <div class="modal-content log-content">
      <div class="modal-header log-header">
        <h3 id="log-title">История изменений маршрутной карты</h3>
        <button type="button" id="log-close" class="btn-secondary">Закрыть</button>
      </div>
      <div class="modal-body log-body">
        <div class="log-card-header">
          <div class="log-barcode-block">
            <canvas id="log-barcode-canvas"></canvas>
            <div class="log-barcode-number" id="log-barcode-number"></div>
          </div>
          <div class="log-card-meta">
            <div><strong>Наименование:</strong> <span id="log-card-name"></span></div>
            <div><strong>Заказ:</strong> <span id="log-card-order"></span></div>
            <div><strong>Статус:</strong> <span id="log-card-status"></span></div>
            <div><strong>Создана:</strong> <span id="log-card-created"></span></div>
          </div>
        </div>
        <div class="log-section">
          <h4>Вид карты при создании</h4>
          <div id="log-initial-view"></div>
        </div>
        <div class="log-section">
          <h4>История изменений</h4>
          <div id="log-history-table"></div>
        </div>
        <div class="log-section">
          <h4>Сводная таблица операций</h4>
          <div id="log-summary-table"></div>
          <div class="log-summary-actions">
            <button type="button" id="log-print-summary" class="btn-primary">Печать сводной таблицы</button>
            <button type="button" id="log-print-all" class="btn-primary">Печать</button>
            <button type="button" id="log-close-bottom" class="btn-secondary">Закрыть</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно для штрихкода -->
  <div id="barcode-modal" class="barcode-modal">
    <div class="barcode-modal-content">
      <h3>Штрихкод технологической карты</h3>
      <p style="font-size:12px; color:#6b7280; margin-top:0;">Формат EAN-13</p>
      <canvas id="barcode-canvas"></canvas>
      <div style="margin-top:8px; font-size:14px;">
        Код: <span id="barcode-modal-code"></span>
      </div>
      <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
        <button id="btn-print-barcode" class="btn-primary">Печать</button>
        <button id="btn-close-barcode" class="btn-secondary">Закрыть</button>
      </div>
    </div>
  </div>
<?php require_once __DIR__ . '/footer.php'; ?>
