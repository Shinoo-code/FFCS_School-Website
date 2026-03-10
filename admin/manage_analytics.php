<?php
// manage_analytics.php
// Simple analytics page showing enrollees by grade (line graphs)
require_once __DIR__ . '/../api/db_connect.php'; // Use __DIR__ for reliable path
require_once __DIR__ . '/../api/session.php';

// Require admin
$role = $_SESSION['faculty_role'] ?? null;
if (empty($_SESSION['faculty_id']) || $role !== 'admin') {
    // Redirect to login or dashboard
    header('Location: login.php');
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Enrollment Analytics - FFCS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #f9fbfc 100%); min-height: 100vh; }
    .page-container { max-width: 1100px; margin: 24px auto; padding: 16px; }
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
    .page-header h3 { font-size: 2rem; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: -0.5px; }
    .back-btn { background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); color: #374151; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
    .back-btn:hover { background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%); transform: translateY(-2px); }
    .page-description { color: #6b7280; font-size: 1rem; margin-bottom: 24px; font-weight: 500; }
    .card { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid rgba(0, 0, 0, 0.05); border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; overflow: hidden; }
    .card:hover { box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
    .card-body { padding: 24px; }
    .chart-card { height: 420px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%); }
    .card-footer { background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border-top: 2px solid #f0f0f0; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
    .card-footer small { color: #8b94a5; font-weight: 500; }
    .card-footer strong { color: #1e293b; font-weight: 700; }
    .total-enrollees { font-size: 1.2rem; color: #0d6efd; font-weight: 700; }
    .action-buttons { margin-top: 20px; display: flex; gap: 10px; }
    #refreshAnalytics { background: linear-gradient(135deg, #0d6efd 0%, #0056d6 100%); color: white; border: none; padding: 11px 24px; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3); }
    #refreshAnalytics:hover { background: linear-gradient(135deg, #0056d6 0%, #003fa9 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4); color: white; }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <h3>Enrollment Analytics</h3>
      <a href="../dashboard.php#dashboard-section" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <p class="page-description"><i class="fas fa-info-circle"></i> Line graphs showing number of enrollees per grade level across the last 12 months.</p>

    <div class="card mb-4">
      <div class="card-body chart-card">
        <canvas id="enrolleesByGradeCanvas" style="width:100%;height:100%"></canvas>
      </div>
      <div class="card-footer">
        <small><i class="fas fa-clock"></i> Data shown for the last 12 months. Refresh to update.</small>
        <div>
          <strong>Total Enrollees: </strong>
          <span class="total-enrollees" id="totalEnrollees">0</span>
        </div>
      </div>
    </div>

    <div class="action-buttons">
      <button id="refreshAnalytics" class="btn"><i class="fas fa-sync"></i> Refresh Analytics</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    let chartInstance = null;

    async function fetchAnalytics() {
      try {
        const resp = await fetch('api/analytics/enrollees_by_grade.php');
        const payload = await resp.json();
        if (!payload.success) {
          console.error('Analytics API error', payload);
          alert('Failed to load analytics: ' + (payload.message || 'unknown'));
          return null;
        }
        return payload;
      } catch (err) {
        console.error(err);
        alert('Failed to load analytics. See console for details.');
        return null;
      }
    }

    function transformPayloadToGradeTotals(payload) {
      // Desired order: Kindergarten, 1..12
      const gradeOrder = ['Kindergarten'];
      for (let i = 1; i <= 12; i++) gradeOrder.push(String(i));

      // Build map grade => total (sum across months)
      const totalsMap = {};
      if (Array.isArray(payload.datasets)) {
        payload.datasets.forEach(ds => {
          const grade = ds.label;
          const sum = Array.isArray(ds.data) ? ds.data.reduce((a,b)=>a+(Number(b)||0),0) : 0;
          totalsMap[grade] = sum;
        });
      }

      // Build arrays aligned to gradeOrder
      const labels = gradeOrder;
      const data = labels.map(g => totalsMap[g] || 0);

      // total across all grades
      const totalAll = data.reduce((a,b) => a + b, 0);

      return { labels, data, totalAll };
    }

    function renderChart(data) {
      const ctx = document.getElementById('enrolleesByGradeCanvas').getContext('2d');
      // Transform payload into grade totals (one dataset)
      const transformed = transformPayloadToGradeTotals(data);
      const colors = ['#007bff'];
      const dataset = {
        label: 'Enrollees',
        data: transformed.data,
        backgroundColor: colors[0],
        borderColor: colors[0]
      };

      // Update total display
      const totalEl = document.getElementById('totalEnrollees');
      if (totalEl) totalEl.textContent = transformed.totalAll;

      if (chartInstance) {
        chartInstance.data.labels = transformed.labels;
        chartInstance.data.datasets = [dataset];
        chartInstance.update();
        return;
      }

      chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: transformed.labels,
          datasets: [dataset]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
          scales: {
            x: { title: { display: true, text: 'Grade Level' } },
            y: {
              title: { display: true, text: 'Enrollees' },
              beginAtZero: true,
              ticks: {
                // show integer tick labels only
                stepSize: 1,
                callback: function(value) { return Math.round(value).toString(); }
              }
            }
          }
        }
      });
    }

    async function loadAndRender() {
      const payload = await fetchAnalytics();
      if (payload) renderChart(payload);
    }

    document.getElementById('refreshAnalytics').addEventListener('click', loadAndRender);

    // Initial load
    loadAndRender();
  </script>
</body>
</html>
