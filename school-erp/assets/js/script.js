// School ERP v2 - Main JS

document.addEventListener('DOMContentLoaded', function () {

    /* ---- Sidebar toggle ---- */
    const toggle   = document.getElementById('sidebarToggle');
    const sidebar  = document.getElementById('sidebar');
    const main     = document.querySelector('.main-content');
    const overlay  = document.getElementById('sidebarOverlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay && overlay.classList.toggle('d-block');
            } else {
                sidebar.classList.toggle('collapsed');
                main && main.classList.toggle('expanded');
                localStorage.setItem('sb', sidebar.classList.contains('collapsed') ? '1' : '0');
            }
        });
    }

    // Restore state
    if (sidebar && window.innerWidth > 768 && localStorage.getItem('sb') === '1') {
        sidebar.classList.add('collapsed');
        main && main.classList.add('expanded');
    }

    overlay && overlay.addEventListener('click', () => {
        sidebar && sidebar.classList.remove('mobile-open');
        overlay.classList.remove('d-block');
    });

    /* ---- Auto-dismiss alerts ---- */
    document.querySelectorAll('.flash-msg').forEach(el => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 5000);
    });

    /* ---- Confirm delete ---- */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    /* ---- Bootstrap tooltips ---- */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    /* ---- Live table search ---- */
    const search = document.getElementById('tableSearch');
    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.searchable tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    /* ---- Mark all present / absent ---- */
    document.getElementById('markAllPresent')?.addEventListener('click', () => {
        document.querySelectorAll('select[name^="status"]').forEach(s => s.value = 'Present');
    });
    document.getElementById('markAllAbsent')?.addEventListener('click', () => {
        document.querySelectorAll('select[name^="status"]').forEach(s => s.value = 'Absent');
    });

    /* ---- Grade color ---- */
    const gradeColors = { 'A+':'#16a34a','A':'#16a34a','B+':'#2563eb','B':'#2563eb','C':'#d97706','D':'#ea580c','F':'#dc2626' };
    document.querySelectorAll('.grade-cell').forEach(c => {
        const g = c.textContent.trim();
        if (gradeColors[g]) { c.style.color = gradeColors[g]; c.style.fontWeight = '700'; }
    });

    /* ---- Print ---- */
    document.getElementById('printPage')?.addEventListener('click', () => window.print());

    /* ---- Admission form multi-step ---- */
    initMultiStep();

    /* ---- PayU auto-submit (if form exists) ---- */
    const payuForm = document.getElementById('payuAutoForm');
    if (payuForm) {
        setTimeout(() => payuForm.submit(), 1500);
    }
});

/* Multi-step form */
function initMultiStep() {
    const steps = document.querySelectorAll('.form-step');
    if (!steps.length) return;

    const nextBtns = document.querySelectorAll('.btn-next-step');
    const prevBtns = document.querySelectorAll('.btn-prev-step');
    let current = 0;

    function show(n) {
        steps.forEach((s, i) => s.classList.toggle('d-none', i !== n));
        document.querySelectorAll('.step-item').forEach((s, i) => {
            s.classList.toggle('active', i === n);
            s.classList.toggle('done', i < n);
        });
        current = n;
    }

    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const cur = steps[current];
            const required = cur.querySelectorAll('[required]');
            let valid = true;
            required.forEach(el => {
                if (!el.value.trim()) { el.classList.add('is-invalid'); valid = false; }
                else el.classList.remove('is-invalid');
            });
            if (valid && current < steps.length - 1) show(current + 1);
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => { if (current > 0) show(current - 1); });
    });

    show(0);
}

/* Toast helper */
function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3`;
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div>
        <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
