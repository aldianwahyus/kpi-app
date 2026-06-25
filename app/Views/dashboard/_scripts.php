<script>
new Chart(document.getElementById('chartPerspektif'), {
  type: 'bar',
  data: {
    labels: ['Financial','Customer','Internal Process','Learning & Growth'],
    datasets: [{
      label: 'Rata-rata Capaian (%)',
      data: [
        <?= $avg_financial ?>,
        <?= $avg_customer  ?>,
        <?= $avg_internal  ?>,
        <?= $avg_learning  ?>
      ],
      backgroundColor: ['#BDD7EE','#C6EFCE','#FFF2CC','#F3E5F5'],
      borderColor:     ['#2E75B6','#375623','#BF9000','#5C2A6B'],
      borderWidth: 1.5,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        max: 150,
        grid: { color: '#f0f0f0' },
        ticks: {
          callback: value => value + '%'
        }
      }
    }
  }
});

// PERBAIKAN: Mengubah total konfigurasi chartGrade ke Grade Baru (M, SB, B, C)
new Chart(document.getElementById('chartGrade'), {
  type: 'doughnut',
  data: {
    // 1. Mengubah nama Label Grafik
    labels: ['M – Memuaskan','SB – Sangat Baik','B – Baik','C – Cukup'],
    datasets: [{
      // 2. Menghubungkan data dengan Key Array Grade Baru dari Controller
      data: [
        <?= $grade_counts['M'] ?? 0 ?>,
        <?= $grade_counts['SB'] ?? 0 ?>,
        <?= $grade_counts['B'] ?? 0 ?>,
        <?= $grade_counts['C'] ?? 0 ?>
      ],
      // 3. Menyesuaikan susunan warna lingkaran doughnut
      backgroundColor: ['#C6EFCE','#BDD7EE','#FFF2CC','#FCE4D6'],
      borderWidth: 0,
    }]
  },
  options: {
    responsive: true,
    cutout: '65%',
    plugins: {
      legend: {
        display: false
      }
    }
  }
});
</script>