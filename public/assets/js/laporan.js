document.addEventListener("DOMContentLoaded", function () {
  console.log("📊 Laporan script loaded");

  const btnCetakPDF = document.getElementById("btnCetakPDF");
  const btnExportExcel = document.getElementById("btnExportExcel");

  // ====================
  // 1. CETAK
  // ====================
  if (btnCetakPDF) {
    btnCetakPDF.addEventListener("click", function () {
      console.log("📄 Opening PDF export...");

      // Buka halaman PDF di tab baru
      window.open("export_pdf.php", "_blank");
    });
  }

  if (btnExportExcel) {
    btnExportExcel.addEventListener("click", function () {
      console.log("📊 Downloading Excel...");

      // Download langsung
      window.location.href = "export_excel.php";
    });
  }

  function printTable() {
    const printContent = document.getElementById("laporanTable").innerHTML;
    const originalContent = document.body.innerHTML;

    document.body.innerHTML = `
            <html>
            <head>
                <title>Cetak Laporan</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                    th { background: #1565c0; color: white; }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                <h2 style="text-align: center;">LAPORAN INVENTORI OBAT</h2>
                ${printContent}
            </body>
            </html>
        `;

    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
  }

  const tableRows = document.querySelectorAll(".table-laporan tbody tr");

  tableRows.forEach((row, index) => {
    row.style.opacity = "0";
    row.style.transform = "translateY(20px)";

    setTimeout(() => {
      row.style.transition = "all 0.3s ease";
      row.style.opacity = "1";
      row.style.transform = "translateY(0)";
    }, index * 30);
  });

  function highlightLowStock() {
    const rows = document.querySelectorAll(".table-laporan tbody tr");

    rows.forEach((row) => {
      const statusBadge = row.querySelector(".badge");
      if (statusBadge) {
        const status = statusBadge.textContent.trim();

        if (status === "Habis" || status === "Menipis") {
          row.style.backgroundColor = "#fff3cd";
        }
      }
    });
  }

  highlightLowStock();

  console.log("✅ Laporan initialized");
});
