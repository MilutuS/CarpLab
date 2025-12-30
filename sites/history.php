<div class="tab-pane fade" id="history">
     <div class="page-header">
        <h1 class="page-title">Logi Systemowe</h1>
        <p class="page-subtitle">Pełna historia wszystkich operacji</p>
    </div>
    <div class="clean-card p-4">
        <button class="btn btn-danger mb-3" onclick="clearLogs()"><i class="bi bi-trash me-2"></i>Wyczyść logi</button>
        <div class="table-responsive">
            <table id="activityHistoryTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 60px;"></th>
                        <th>Opis</th>
                        <th>Użytkownik</th>
                        <th>Data i czas</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
