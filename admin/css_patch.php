<style>
@import url('https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');
body {
  margin: 0;
  font-family: 'Inter', system-ui, sans-serif;
  color: #333333;
  background: #ededed;
  overflow-x: hidden;
}
.app-header {
  background: white; border-bottom: 1px solid #e5e5e5; padding: 16px 32px;
  display: flex; justify-content: space-between; align-items: center;
  position: sticky; top: 0; z-index: 100;
}
.app-header h1 { margin: 0; font-family: Oswald, sans-serif; text-transform: uppercase; font-size: 20px; color: #1d3557; display: flex; align-items: center; gap: 12px; }
.header-actions { display: flex; gap: 12px; }
.btn-nav { text-decoration: none; padding: 8px 16px; font-size: 13px; font-weight: 600; color: #457b9d; background: #f0f4f8; border-radius: 6px; border: 1px solid #d0e0ec; transition: 0.2s; }
.btn-nav.active { background: #e63946; color: white; border-color: #e63946; }
.btn-nav:hover:not(.active) { background: #e0ebf3; }
.btn-primary { text-decoration: none; padding: 8px 16px; font-size: 13px; font-weight: 600; color: white; background: #1d3557; border-radius: 6px; border: none; cursor: pointer; }
.btn-primary:hover { background: #142845; }
.main-container { padding: 32px; max-width: 1200px; margin: 0 auto; }
.panel-card { background: white; border-radius: 12px; border: 1px solid #e5e5e5; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
.products-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.products-table th { text-align: left; padding: 12px 10px; border-bottom: 2px solid #e63946; color: #1d3557; font-weight: 600; white-space: nowrap; }
.products-table td { padding: 12px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.products-table tr:hover { background: #fdfdfd; }
.action-link { color: #3182ce; text-decoration: none; font-weight: 600; margin-right: 12px; }
.action-link.danger { color: #e53e3e; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #727272; margin-bottom: 6px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; box-sizing: border-box; background: #f7f7f7; border: 1px solid #e5e5e5; border-radius: 6px; padding: 10px 12px; font-size: 14px; transition: border-color 0.2s; outline:none; }
.form-group input:focus, .form-group select:focus { border-color: #457b9d; }
</style>
