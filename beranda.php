<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Guru</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background-color: #d9ebd0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .container {
        width: 360px;
        background-color: #f8f8f8;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Header */
    .header {
        background-color: #f68c2e;
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: white;
    }

    .header-info h3 {
        font-size: 16px;
        font-weight: 600;
    }

    .header-info p {
        font-size: 12px;
        opacity: 0.9;
    }

    /* Card Section */
    .card-section {
        background-color: #4d6651;
        padding: 15px;
        display: flex;
        justify-content: center;
    }

    .cards {
        background-color: #f8fff6;
        border-radius: 12px;
        padding: 15px;
        width: 90%;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .card {
        background-color: white;
        border-radius: 8px;
        padding: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 3px rgba(0,0,0,0.05);
    }

    .card i {
        font-size: 20px;
        color: #4d6651;
    }

    .card p {
        font-size: 13px;
        color: #333;
    }

    /* Urgent Info */
    .urgent {
        background-color: #f68c2e;
        color: white;
        margin: 15px;
        border-radius: 10px;
        padding: 12px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 3px 4px rgba(0,0,0,0.1);
    }

    /* Quick Access */
    .quick {
        padding: 10px 20px 20px;
    }

    .quick h4 {
        font-size: 14px;
        color: #333;
        margin-bottom: 10px;
    }

    .quick-access {
        display: flex;
        gap: 10px;
    }

    .quick-btn {
        flex: 1;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }

    .btn-green { background-color: #4d6651; }
    .btn-orange { background-color: #f68c2e; }
    .btn-gray { background-color: #a0b5a0; }

    /* Responsif */
    @media (max-width: 400px) {
        .container {
            width: 95%;
        }
        .header-info h3 { font-size: 14px; }
        .card p { font-size: 12px; }
    }
</style>
<!-- icon fontawesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

<?php
    $nama = "Farah Nur Fauziyyah";
    $kelas = "Guru - 4A";
    $hadir = "27/30 Siswa";
    $jadwal = "Sejarah";
    $urgent = "Rapat guru pukul 13.30\nRuang serbaguna";
?>

<div class="container">
    <div class="header">
        <img src="https://via.placeholder.com/40" alt="Profile">
        <div class="header-info">
            <h3><?= $nama ?></h3>
            <p><?= $kelas ?></p>
        </div>
    </div>

    <div class="card-section">
        <div class="cards">
            <div class="card"><i class="fas fa-user-check"></i><p>Hadir Hari Ini : <?= $hadir ?></p></div>
            <div class="card"><i class="fas fa-calendar-alt"></i><p>Jadwal Berikutnya : <?= $jadwal ?></p></div>
            <div class="card"><i class="fas fa-user-check"></i><p>Hadir Hari Ini : <?= $hadir ?></p></div>
        </div>
    </div>

    <div class="urgent">
        <b>URGENT INFO</b><br>
        <?= nl2br($urgent) ?>
    </div>

    <div class="quick">
        <h4>Quick Access</h4>
        <div class="quick-access">
            <div class="quick-btn btn-green"><i class="fas fa-users"></i></div>
            <div class="quick-btn btn-orange"><i class="fas fa-book"></i></div>
            <div class="quick-btn btn-gray"><i class="fas fa-cog"></i></div>
        </div>
    </div>
</div>

</body>
</html>
