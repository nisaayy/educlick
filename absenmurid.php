<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi Wali Murid</title>
<style>
    body {
        font-family: "Poppins", sans-serif;
        background: #d7f3cc;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
    }

    .container {
        width: 360px;
        background: #e8f6de;
        border-radius: 20px;
        overflow: hidden;
        margin-top: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .header {
        background: #7ecb62;
        color: white;
        text-align: center;
        padding: 15px 0;
    }

    .calendar {
        background: #6cb857;
        color: white;
        padding: 10px;
        text-align: center;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        margin-top: 10px;
    }

    .day {
        padding: 8px;
        border-radius: 8px;
        cursor: pointer;
    }

    .day:hover {
        background: rgba(255,255,255,0.2);
    }

    .selected {
        background: #fff;
        color: #6cb857;
        font-weight: bold;
    }

    .absensi-box {
        background: #bfe7a1;
        border-radius: 15px;
        margin: 20px;
        padding: 15px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .absensi-title {
        text-align: center;
        color: #3c763d;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        border-radius: 12px;
        padding: 10px 15px;
        margin-bottom: 10px;
    }

    .status {
        padding: 5px 10px;
        border-radius: 8px;
        color: white;
        font-size: 13px;
        font-weight: bold;
    }

    .hadir { background: #4caf50; }
    .alpa { background: #f44336; }
    .izin { background: #ffb300; }
    .sakit { background: #e91e63; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h3>Absensi</h3>
        <p>Kelas 4A</p>
    </div>

    <div class="calendar">
        <div class="calendar-header">
            <button id="prevMonth" style="background:none;border:none;color:white;font-size:20px;">&#8592;</button>
            <span id="monthYear">January 2025</span>
            <button id="nextMonth" style="background:none;border:none;color:white;font-size:20px;">&#8594;</button>
        </div>
        <div class="calendar-grid" id="calendarDays"></div>
    </div>

    <div class="absensi-box" id="absensiBox" style="display:none;">
        <div class="absensi-title">Absensi Kehadiran</div>
        <div id="absensiList"></div>
    </div>
</div>

<script>
const monthYear = document.getElementById("monthYear");
const calendarDays = document.getElementById("calendarDays");
const absensiBox = document.getElementById("absensiBox");
const absensiList = document.getElementById("absensiList");

let currentDate = new Date(2025, 0); // Januari 2025 default

function renderCalendar(date) {
    calendarDays.innerHTML = "";
    const year = date.getFullYear();
    const month = date.getMonth();
    monthYear.textContent = date.toLocaleString("id-ID", { month: "long", year: "numeric" });

    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement("div");
        calendarDays.appendChild(empty);
    }

    for (let day = 1; day <= lastDate; day++) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("day");
        dayDiv.textContent = day;
        dayDiv.onclick = () => showAbsensi(day);
        calendarDays.appendChild(dayDiv);
    }
}

function showAbsensi(day) {
    document.querySelectorAll(".day").forEach(d => d.classList.remove("selected"));
    event.target.classList.add("selected");
    absensiBox.style.display = "block";

    // simulasi ambil data dari database
    const data = [
        { mapel: "Bahasa Indonesia", jam: "08.00 - 11.00", status: "Hadir" },
        { mapel: "Bahasa Indonesia", jam: "08.00 - 11.00", status: "Alpa" },
        { mapel: "Bahasa Indonesia", jam: "08.00 - 11.00", status: "Izin" },
        { mapel: "Bahasa Indonesia", jam: "08.00 - 11.00", status: "Sakit" }
    ];

    absensiList.innerHTML = "";
    data.forEach(item => {
        const div = document.createElement("div");
        div.className = "item";
        div.innerHTML = `
            <div>
                <strong>${item.mapel}</strong><br>
                <small>${item.jam}</small>
            </div>
            <div class="status ${item.status.toLowerCase()}">${item.status}</div>
        `;
        absensiList.appendChild(div);
    });
}

document.getElementById("prevMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
    absensiBox.style.display = "none";
};

document.getElementById("nextMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
    absensiBox.style.display = "none";
};

renderCalendar(currentDate);
</script>

</body>
</html>
