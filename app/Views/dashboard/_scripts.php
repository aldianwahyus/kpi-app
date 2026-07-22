<script>
new Chart(document.getElementById('chartPerspektif'), {
  type: 'bar',
  data: {
    labels: ['Financial','Customer','Internal Process','Learning & Growth'],
    datasets: [{
      label: 'Rata-rata Skor (skala 1-4)',
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
        max: 4,
        grid: { color: '#f0f0f0' },
      }
    }
  }
});

// Grade sesuai skema kriteria pencapaian (Istimewa/Baik/Cukup/Kurang)
new Chart(document.getElementById('chartGrade'), {
  type: 'doughnut',
  data: {
    labels: ['IS – Istimewa','SB – Sangat Baik','B – Baik','C – Cukup'],
    datasets: [{
      data: [
        <?= $grade_counts['IS'] ?? 0 ?>,
        <?= $grade_counts['SB'] ?? 0 ?>,
        <?= $grade_counts['B'] ?? 0 ?>,
        <?= $grade_counts['C'] ?? 0 ?>
      ],
      backgroundColor: ['#1E7A55','#A9D18E','#FFC000','#FCE4D6'],
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