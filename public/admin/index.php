<?php
$baseApi = '/api';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Masjid Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Smart Masjid Admin</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small">JWT Auth · OTP for users</span>
            <button id="logoutBtn" class="btn btn-outline-light btn-sm">Logout</button>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div class="row mb-3 g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Admin Login</h5>
                    <p class="text-muted small">Super Admins use password; app users rely on OTP.</p>
                    <form id="loginForm" class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary" type="submit">Login</button>
                        </div>
                    </form>
                    <div class="alert alert-info mt-3 small">Use default SUPER admin or create via SQL seed.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Prayer Time Management</h5>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Mosque ID</label>
                            <input type="number" id="mosqueId" class="form-control" placeholder="e.g. 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prayer Date</label>
                            <input type="date" id="prayerDate" class="form-control">
                        </div>
                        <div class="col-md-4"><label class="form-label">Fajr</label><input id="fajr" class="form-control" type="time"></div>
                        <div class="col-md-4"><label class="form-label">Zuhr</label><input id="zuhr" class="form-control" type="time"></div>
                        <div class="col-md-4"><label class="form-label">Asr</label><input id="asr" class="form-control" type="time"></div>
                        <div class="col-md-4"><label class="form-label">Maghrib</label><input id="maghrib" class="form-control" type="time"></div>
                        <div class="col-md-4"><label class="form-label">Isha</label><input id="isha" class="form-control" type="time"></div>
                        <div class="col-md-4"><label class="form-label">Jumu'ah</label><input id="jummah" class="form-control" type="time"></div>
                        <div class="col-12 d-flex gap-2 mt-3">
                            <button class="btn btn-success" id="saveDaily">Save Daily Times</button>
                            <button class="btn btn-outline-secondary" id="saveJummah">Save Jumu'ah</button>
                            <button class="btn btn-outline-primary" id="loadToday">Load Today</button>
                        </div>
                    </div>
                    <hr>
                    <div id="apiResponse" class="small text-monospace"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">OTP Tester</h5>
                    <form id="otpForm" class="row g-2">
                        <div class="col-12"><label class="form-label">Phone</label><input class="form-control" name="phone" required></div>
                        <div class="col-12 d-grid"><button class="btn btn-outline-primary" type="submit">Request OTP</button></div>
                    </form>
                    <div id="otpResult" class="mt-2 small"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Mosque Directory (GET /mosques)</h5>
                    <button class="btn btn-outline-primary btn-sm mb-2" id="loadMosques">Load Mosques</button>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead><tr><th>ID</th><th>Name</th><th>Address</th><th>Status</th></tr></thead>
                            <tbody id="mosqueTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const apiBase = '<?= $baseApi ?>';
let accessToken = '';

function notify(target, data) {
    $(target).text(JSON.stringify(data, null, 2));
}

$('#loginForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: `${apiBase}/auth/admin/login`,
        method: 'POST',
        data: JSON.stringify(Object.fromEntries(new FormData(this))),
        contentType: 'application/json',
        success: (data) => { accessToken = data.access_token; notify('#apiResponse', data); },
        error: (xhr) => notify('#apiResponse', xhr.responseJSON)
    });
});

$('#saveDaily').on('click', function() {
    const payload = {
        mosque_id: Number($('#mosqueId').val()),
        prayer_date: $('#prayerDate').val(),
        fajr: $('#fajr').val(),
        zuhr: $('#zuhr').val(),
        asr: $('#asr').val(),
        maghrib: $('#maghrib').val(),
        isha: $('#isha').val(),
    };
    $.ajax({
        url: `${apiBase}/admin/prayer-times/update`,
        method: 'POST',
        headers: { 'Authorization': `Bearer ${accessToken}` },
        data: JSON.stringify(payload),
        contentType: 'application/json',
        success: (data) => notify('#apiResponse', data),
        error: (xhr) => notify('#apiResponse', xhr.responseJSON)
    });
});

$('#saveJummah').on('click', function() {
    const payload = {
        mosque_id: Number($('#mosqueId').val()),
        jummah: $('#jummah').val(),
    };
    $.ajax({
        url: `${apiBase}/admin/jummah`,
        method: 'POST',
        headers: { 'Authorization': `Bearer ${accessToken}` },
        data: JSON.stringify(payload),
        contentType: 'application/json',
        success: (data) => notify('#apiResponse', data),
        error: (xhr) => notify('#apiResponse', xhr.responseJSON)
    });
});

$('#loadToday').on('click', function() {
    const id = Number($('#mosqueId').val());
    $.ajax({
        url: `${apiBase}/mosques/${id}/today`,
        method: 'GET',
        headers: { 'Authorization': `Bearer ${accessToken}` },
        success: (data) => notify('#apiResponse', data),
        error: (xhr) => notify('#apiResponse', xhr.responseJSON)
    });
});

$('#loadMosques').on('click', function() {
    $.ajax({
        url: `${apiBase}/mosques`,
        method: 'GET',
        headers: { 'Authorization': `Bearer ${accessToken}` },
        success: (data) => {
            const rows = data.map(row => `<tr><td>${row.mosque_id}</td><td>${row.name}</td><td>${row.address}</td><td>${row.is_active ? 'Active' : 'Inactive'}</td></tr>`);
            $('#mosqueTable').html(rows.join(''));
        },
        error: (xhr) => notify('#apiResponse', xhr.responseJSON)
    });
});

$('#otpForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: `${apiBase}/auth/request-otp`,
        method: 'POST',
        data: JSON.stringify(Object.fromEntries(new FormData(this))),
        contentType: 'application/json',
        success: (data) => $('#otpResult').text('OTP (dev only): ' + data.otp),
        error: (xhr) => $('#otpResult').text(JSON.stringify(xhr.responseJSON))
    });
});

$('#logoutBtn').on('click', function() {
    accessToken = '';
    $('#apiResponse').text('Logged out on client side');
});
</script>
</body>
</html>
