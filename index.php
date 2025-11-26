<?php require_once __DIR__ . '/header.php'; ?>
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
              <label for="card-drawing">Чертёж / обозначение</label>
              <input id="card-drawing" />
            </div>
            <div class="flex-col">
              <label for="card-material">Материал / марка</label>
              <input id="card-material" />
            </div>
          </div>

          <label for="card-desc">Доп. описание</label>
          <textarea id="card-desc" rows="3"></textarea>

          <div class="route-header">
            <h3>Маршрутная таблица</h3>
            <div class="flex" style="gap:8px;">
              <input id="route-filter" placeholder="Поиск по коду, названию, участку или исполнителю" />
              <button type="button" class="btn-secondary" id="route-filter-clear">Сбросить</button>
            </div>
          </div>

          <div class="route-table-wrapper">
            <table class="table route-table" id="route-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Код</th>
                  <th>Операция</th>
                  <th>Участок</th>
                  <th>Исполнитель</th>
                  <th>План, мин</th>
                  <th>Статус</th>
                  <th>Норма, шт</th>
                  <th>Брак, шт</th>
                  <th>Ожид., шт</th>
                  <th>Комментарий</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="route-body"></tbody>
            </table>
          </div>

          <div class="flex" style="gap:8px;">
            <button type="button" class="btn-secondary" id="route-add">Добавить строку</button>
            <button type="button" class="btn-secondary" id="route-autofill">Заполнить по шаблону</button>
            <button type="button" class="btn-secondary" id="route-clear">Очистить таблицу</button>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" id="card-cancel">Отмена</button>
        <button class="btn-primary" id="card-save">Сохранить</button>
      </div>
    </div>
  </div>

  <div id="barcode-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="barcode-modal-title">
    <div class="modal-content barcode-modal-content">
      <div class="modal-header">
        <h2 id="barcode-modal-title">Штрихкод EAN-13</h2>
      </div>
      <div class="modal-body" id="barcode-body"></div>
      <div class="modal-footer">
        <button class="btn-secondary" data-close="barcode-modal">Закрыть</button>
      </div>
    </div>
  </div>

  <div id="attachments-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="attachments-modal-title">
    <div class="modal-content attachments-modal-content">
      <div class="modal-header">
        <h2 id="attachments-modal-title">Файлы карты</h2>
      </div>
      <div class="modal-body" id="attachments-body">
        <div class="flex" style="gap:8px; align-items:flex-start;">
          <label class="file-label">
            <input type="file" id="attach-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar,.7z" />
            <span>Прикрепить файлы</span>
          </label>
          <p style="font-size:12px; color:#6b7280; margin:0;">Допустимые форматы: pdf, doc/docx, jpg/png, zip/rar/7z. Максимум 15 МБ за файл.</p>
        </div>
        <div id="attachments-list"></div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" data-close="attachments-modal">Закрыть</button>
      </div>
    </div>
  </div>

  <div id="printable-area" class="hidden" aria-hidden="true"></div>

  <div id="log-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="log-modal-title">
    <div class="modal-content log-modal-content">
      <div class="modal-header">
        <h2 id="log-modal-title">История изменений маршрутной карты</h2>
      </div>
      <div class="modal-body log-modal-body">
        <div id="log-card-details" class="log-card-details"></div>
        <div id="log-table-wrapper" class="log-table-wrapper"></div>
        <div id="log-summary-wrapper" class="log-summary-wrapper"></div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" data-close="log-modal">Закрыть</button>
        <button class="btn-primary" id="log-print">Печать сводной таблицы</button>
      </div>
    </div>
  </div>

  <div id="receipt-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title">
    <div class="modal-content receipt-modal-content">
      <div class="modal-header">
        <h2 id="receipt-modal-title">Маршрутная квитанция</h2>
      </div>
      <div class="modal-body" id="receipt-body"></div>
      <div class="modal-footer">
        <button class="btn-secondary" data-close="receipt-modal">Закрыть</button>
        <button class="btn-primary" id="receipt-print">Печать</button>
      </div>
    </div>
  </div>
<?php require_once __DIR__ . '/footer.php'; ?>
