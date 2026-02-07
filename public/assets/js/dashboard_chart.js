document.addEventListener("DOMContentLoaded", () => {
  const ctx = document.getElementById("stokChart").getContext("2d");

  const startInput = document.getElementById("startDate");
  const endInput = document.getElementById("endDate");
  const btnFilter = document.getElementById("btnFilter");
  const btnReset = document.getElementById("btnResetFilter");

  let chartInstance = null;

  // ================================
  // DEFAULT: BULAN SAAT INI
  // ================================
  const now = new Date();
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

  const toYMD = (d) => d.toISOString().split("T")[0];

  startInput.value = toYMD(firstDay);
  endInput.value = toYMD(lastDay);

  // ================================
  async function loadChart(start, end) {
    const res = await fetch(
      `dashboard_chart_data.php?start=${start}&end=${end}`
    );
    const data = await res.json();

    const config = {
      type: "bar",
      data: {
        labels: data.labels,
        datasets: [
          {
            label: "Obat Masuk",
            data: data.masuk,
            backgroundColor: "#1e88e5",
            borderRadius: 6,
          },
          {
            label: "Obat Keluar",
            data: data.keluar,
            backgroundColor: "#0d47a1",
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top" },
        },
        scales: {
          y: { beginAtZero: true },
        },
      },
    };

    if (chartInstance) {
      chartInstance.destroy();
    }
    chartInstance = new Chart(ctx, config);
  }

  // LOAD AWAL
  loadChart(startInput.value, endInput.value);

  // FILTER
  btnFilter.addEventListener("click", () => {
    loadChart(startInput.value, endInput.value);
    btnReset.style.display = "inline-block";
  });

  // RESET
  btnReset.addEventListener("click", () => {
    startInput.value = toYMD(firstDay);
    endInput.value = toYMD(lastDay);
    loadChart(startInput.value, endInput.value);
    btnReset.style.display = "none";
  });
});
