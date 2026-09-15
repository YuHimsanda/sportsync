// ============ SportsSync Command Center JS ============

// Toast helper
const toast = document.getElementById('toast');
const toastMsg = document.getElementById('toastMsg');
let toastTimer;
function showToast(msg, ms = 2400) {
  toastMsg.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), ms);
}

// ============ Live match clock (Match Management page) ============
const clockEl = document.getElementById('liveClock');
if (clockEl) {
  let baseMinutes = 90;
  let baseSeconds = 4;
  setInterval(() => {
    baseSeconds++;
    if (baseSeconds >= 60) {
      baseSeconds = 0;
      baseMinutes++;
    }
    clockEl.textContent = baseMinutes + ' + ' + (baseMinutes > 90 ? baseSeconds : 4);
  }, 8000);
}

// ============ Sidebar nav active state ============
// (Already handled by active class in HTML, but allow click feedback on Settings items)
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', (e) => {
    // If it's a "#" link, prevent default and show toast
    if (item.getAttribute('href') === '#') {
      e.preventDefault();
      const label = item.querySelector('span').textContent;
      showToast('Opening: ' + label);
    }
  });
});

// ============ Topbar interactions ============
const searchInput = document.querySelector('.search input');
if (searchInput) {
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      searchInput.focus();
    }
    if (e.key === 'Escape' && document.activeElement === searchInput) {
      searchInput.blur();
    }
  });

  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const q = searchInput.value.trim();
      if (q) showToast('Searching for "' + q + '"…');
    }
  });
}

document.querySelectorAll('.icon-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const title = btn.getAttribute('title');
    if (title === 'Notifications') {
      showToast('You have 3 new alerts');
    } else if (title === 'Settings') {
      showToast('Settings panel opening…');
    }
  });
});

const profile = document.querySelector('.profile');
if (profile) {
  profile.addEventListener('click', () => {
    showToast('Profile menu — Alex Kerr (Ops Director)');
  });
}

// ============ Generic button handlers ============
document.querySelectorAll('.btn-primary').forEach(btn => {
  btn.addEventListener('click', () => {
    const text = btn.textContent.trim();
    showToast(text + ' — action initiated');
  });
});

document.querySelectorAll('.btn-ghost').forEach(btn => {
  btn.addEventListener('click', () => {
    const text = btn.textContent.trim();
    showToast(text + ' view activated');
  });
});

// Dropdown
document.querySelectorAll('.dropdown-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    showToast('Filter options: Today / Yesterday / This week');
  });
});

// Links with href="#"
document.querySelectorAll('a[href="#"]').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const text = link.textContent.trim();
    showToast('Loading: ' + text);
  });
});

// Generate Report button
const reportBtn = document.querySelector('.btn-report');
if (reportBtn) {
  reportBtn.addEventListener('click', () => {
    const originalHTML = reportBtn.innerHTML;
    reportBtn.innerHTML = `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" style="width:14px;height:14px;animation:spin 1s linear infinite">
        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
      </svg>
      Generating…
    `;
    reportBtn.disabled = true;
    showToast('Generating match report…');
    setTimeout(() => {
      reportBtn.innerHTML = originalHTML;
      reportBtn.disabled = false;
      showToast('Report ready! Downloaded to /reports');
    }, 2200);
  });
}

// Expand view
document.querySelectorAll('.expand-link').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    showToast('Expanding to full match view…');
  });
});

// Table rows
document.querySelectorAll('.table tbody tr').forEach(row => {
  row.style.cursor = 'pointer';
  row.addEventListener('click', () => {
    const firstCell = row.querySelector('td');
    if (firstCell) {
      const text = firstCell.textContent.trim().split('\n')[0];
      showToast('Opening details for ' + text);
    }
  });
});

// ============ Filter tabs (Team page) ============
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    showToast('Filter: ' + tab.textContent.trim());
  });
});

// ============ Tabs (Ranking page) ============
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    showToast('View: ' + tab.textContent.trim());
  });
});

// ============ Respond buttons (Security Center) ============
document.querySelectorAll('.btn-respond').forEach(btn => {
  btn.addEventListener('click', () => {
    const text = btn.textContent.trim();
    showToast(text + ' — action logged');
  });
});

// ============ View Details buttons (Team page) ============
document.querySelectorAll('.btn-view-team').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    const card = btn.closest('.team-card');
    const name = card ? card.querySelector('.team-card-name').textContent : 'team';
    showToast('Loading ' + name + ' team details…');
  });
});

// ============ Donut chart — re-trigger animation on view ============
const donutObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const segments = entry.target.querySelectorAll('.donut-seg');
      segments.forEach(seg => {
        seg.style.animation = 'none';
        void seg.offsetWidth;
        seg.style.animation = '';
      });
    }
  });
}, { threshold: 0.5 });

const donutEl = document.querySelector('.donut');
if (donutEl) donutObserver.observe(donutEl);

// ============ Spin keyframe ============
const style = document.createElement('style');
style.textContent = `@keyframes spin { to { transform: rotate(360deg); } }`;
document.head.appendChild(style);

// ============ Welcome toast on load ============
window.addEventListener('load', () => {
  setTimeout(() => {
    const pageTitle = document.querySelector('.page-title');
    if (pageTitle) {
      showToast('Welcome back, Alex! — ' + pageTitle.textContent);
    }
  }, 600);
});
