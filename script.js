// Initialize icons
try {
  feather.replace();
} catch (e) {
  console.error("Feather icons failed to load:", e);
}

// Theme Toggle Logic
const themeToggleBtn = document.getElementById('themeToggle');
const htmlEl = document.documentElement;

function updateThemeIcon(theme) {
  const iconName = theme === 'dark' ? 'sun' : 'moon';
  themeToggleBtn.innerHTML = `<i data-feather="${iconName}" id="themeIcon"></i>`;
  feather.replace();
}

// Initialize theme
try {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    htmlEl.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    htmlEl.setAttribute('data-theme', 'dark');
    updateThemeIcon('dark');
  }
} catch (e) {
  console.error("localStorage access failed:", e);
}

themeToggleBtn.addEventListener('click', () => {
  console.log("Theme toggle clicked!");
  const currentTheme = htmlEl.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  htmlEl.setAttribute('data-theme', newTheme);
  try {
    localStorage.setItem('theme', newTheme);
  } catch (e) {
    console.error("Failed to save theme:", e);
  }
  updateThemeIcon(newTheme);
  
  // Re-render chart to match theme colors
  if (typeof semesterChartInstance !== 'undefined' && semesterChartInstance !== null) {
      fetchStats(); 
  }
});
document.getElementById('currentYear').textContent = new Date().getFullYear();

// --- Desktop Mode Warning ---
document.addEventListener('DOMContentLoaded', () => {
  const desktopModeModal = document.getElementById('desktopModeModal');
  const closeDesktopModeModal = document.getElementById('closeDesktopModeModal');
  const btnAcknowledgeDesktopMode = document.getElementById('btnAcknowledgeDesktopMode');

  // Check if screen is mobile-sized (<= 768px)
  if (window.innerWidth <= 768) {
    // Show every time on load as requested
    setTimeout(() => {
      desktopModeModal.classList.add('show');
    }, 500);
  }

  const hideDesktopWarning = () => {
    desktopModeModal.classList.remove('show');
  };

  if (closeDesktopModeModal) closeDesktopModeModal.addEventListener('click', hideDesktopWarning);
  if (btnAcknowledgeDesktopMode) btnAcknowledgeDesktopMode.addEventListener('click', hideDesktopWarning);
});

// --- Dashboard Logic ---
const statUploads = document.getElementById('statUploads');
const statDownloads = document.getElementById('statDownloads');
let semesterChartInstance = null;

async function fetchStats() {
  try {
    const response = await fetch('backend/stats.php');
    const result = await response.json();
    if (result.status === 'success') {
      statUploads.textContent = result.data.totalUploads;
      statDownloads.textContent = result.data.totalDownloads;
      
      if (result.data.semesterData) {
          renderChart(result.data.semesterData);
      }
      
      if (result.data.topContributors) {
          renderLeaderboard(result.data.topContributors);
      }
    }
  } catch (error) {
    console.error("Failed to load stats", error);
  }
}

function renderLeaderboard(contributors) {
  const listEl = document.getElementById('leaderboardList');
  if (!listEl) return;
  
  listEl.innerHTML = '';
  
  if (!contributors || contributors.length === 0) {
    listEl.innerHTML = '<p style="text-align: center; color: var(--text-secondary); font-size: 0.9rem; margin-top: 1rem;">No contributors yet. Be the first!</p>';
    return;
  }

  const medals = ['🥇', '🥈', '🥉'];
  
  contributors.forEach((user, index) => {
    const medal = medals[index] || '';
    const itemHtml = `
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--glass-border); box-shadow: var(--shadow-sm);">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
          <div style="font-size: 1.5rem; line-height: 1;">${medal}</div>
          <div>
            <p style="margin: 0; font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">${user.studentName}</p>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">${user.department}</p>
          </div>
        </div>
        <div style="text-align: right;">
          <span style="display: inline-flex; align-items: center; justify-content: center; background: #dbeafe; color: #2563eb; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">${user.uploads}</span>
        </div>
      </div>
    `;
    listEl.innerHTML += itemHtml;
  });
}

function renderChart(semesterData) {
  const ctx = document.getElementById('semesterChart');
  if (!ctx) return;

  const allSemesters = ["1st", "2nd", "3rd", "4th", "5th", "6th", "7th", "8th"];
  const dataMap = {};
  
  allSemesters.forEach(s => dataMap[s] = 0);
  
  semesterData.forEach(item => {
    if(dataMap[item.semester] !== undefined) {
       dataMap[item.semester] = parseInt(item.count);
    }
  });

  const chartData = allSemesters.map(s => dataMap[s]);
  const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDarkMode ? '#e2e8f0' : '#475569';
  const gridColor = isDarkMode ? '#334155' : '#e2e8f0';

  if (semesterChartInstance) {
    semesterChartInstance.data.datasets[0].data = chartData;
    semesterChartInstance.options.scales.x.ticks.color = textColor;
    semesterChartInstance.options.scales.y.ticks.color = textColor;
    semesterChartInstance.options.scales.x.grid.color = gridColor;
    semesterChartInstance.options.scales.y.grid.color = gridColor;
    semesterChartInstance.update();
  } else {
    semesterChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: allSemesters.map(s => s + ' Sem'),
        datasets: [{
          label: 'Number of Papers',
          data: chartData,
          backgroundColor: 'rgba(59, 130, 246, 0.6)',
          borderColor: 'rgba(59, 130, 246, 1)',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                color: textColor
            },
            grid: {
                color: gridColor
            }
          },
          x: {
              ticks: { color: textColor },
              grid: { display: false, color: gridColor }
          }
        }
      }
    });
  }
}

// Initial fetch
fetchStats();

// --- Modal Logic ---
const uploadModal = document.getElementById('uploadModal');
const downloadModal = document.getElementById('downloadModal');

// Open Upload
const openUploadBtn = document.getElementById('openUploadModal');
if (openUploadBtn) {
  openUploadBtn.addEventListener('click', () => {
    uploadModal.classList.add('show');
  });
}
document.querySelectorAll('.open-upload-modal').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    uploadModal.classList.add('show');
  });
});

// Close Upload
document.getElementById('closeUploadModal').addEventListener('click', () => {
  uploadModal.classList.remove('show');
  resetUploadModal();
});

// Open Download
const openDownloadBtn = document.getElementById('openDownloadModal');
if (openDownloadBtn) {
  openDownloadBtn.addEventListener('click', () => {
    downloadModal.classList.add('show');
  });
}
document.querySelectorAll('.open-download-modal').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    downloadModal.classList.add('show');
  });
});

// Close Download
document.getElementById('closeDownloadModal').addEventListener('click', () => {
  downloadModal.classList.remove('show');
});


// --- Upload Flow Logic ---
const step1 = document.getElementById('step1-selection');
const step2 = document.getElementById('step2-cropper');
const step3 = document.getElementById('step3-metadata');
const modalTitle = document.getElementById('uploadModalTitle');

const cameraInput = document.getElementById('cameraInput');
const galleryInput = document.getElementById('galleryInput');
const imageToCrop = document.getElementById('imageToCrop');
const finalPreviewImg = document.getElementById('finalPreviewImg');

let cropper = null;
let currentRotation = 0;
let turnstileWidgetId = null;
let scannedPages = []; // Store multiple cropped pages

document.getElementById('btnCamera').addEventListener('click', () => cameraInput.click());
document.getElementById('btnGallery').addEventListener('click', () => galleryInput.click());

function handleFileSelect(event) {
  const file = event.target.files[0];
  if (file) {
    // 1. Instantly switch UI to show progress
    step1.classList.remove('active');
    step1.classList.add('hidden');
    step2.classList.remove('hidden');
    step2.classList.add('active');
    modalTitle.textContent = "Loading Image...";
    
    // Clear previous cropper immediately if exists
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
    
    // 2. Yield to browser to paint the "Loading..." UI, then load image
    setTimeout(() => {
      const url = URL.createObjectURL(file);
      imageToCrop.src = url;
      
      imageToCrop.onload = () => {
        modalTitle.textContent = "Draw Crop Box";
        cropper = new Cropper(imageToCrop, {
          viewMode: 1,
          dragMode: 'crop',
          background: false,
        });
      };
    }, 10);
  }
}

cameraInput.addEventListener('change', handleFileSelect);
galleryInput.addEventListener('change', handleFileSelect);

// Rotation Controls
document.getElementById('rotationSlider').addEventListener('input', (e) => {
  currentRotation = parseInt(e.target.value);
  if (cropper) cropper.rotateTo(currentRotation);
});

document.getElementById('btnRotate90').addEventListener('click', () => {
  currentRotation = (currentRotation + 90) % 360;
  document.getElementById('rotationSlider').value = currentRotation;
  if (cropper) cropper.rotateTo(currentRotation);
});

// Cancel Crop
document.getElementById('btnCancelCrop').addEventListener('click', resetUploadModal);

// Confirm Crop
document.getElementById('btnConfirmCrop').addEventListener('click', () => {
  if (!cropper) return;
  
  const canvas = cropper.getCroppedCanvas({
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high',
  });
  
  const base64Img = canvas.toDataURL('image/jpeg');
  scannedPages.push(base64Img);

  step2.classList.remove('active');
  step2.classList.add('hidden');
  
  // Show Pages step instead of Metadata
  const step25 = document.getElementById('step2-5-pages');
  step25.classList.remove('hidden');
  step25.classList.add('active');
  modalTitle.textContent = "Scanned Pages";
  
  renderPagesPreview();
});

function renderPagesPreview() {
  const container = document.getElementById('pagesPreviewContainer');
  container.innerHTML = '';
  
  scannedPages.forEach((pageSrc, index) => {
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    wrapper.style.flexShrink = '0';
    
    const img = document.createElement('img');
    img.src = pageSrc;
    img.style.height = '150px';
    img.style.borderRadius = '8px';
    img.style.boxShadow = 'var(--shadow-sm)';
    img.style.border = '1px solid var(--glass-border)';
    
    const deleteBtn = document.createElement('button');
    deleteBtn.innerHTML = '<i data-feather="x" style="width: 14px; height: 14px;"></i>';
    deleteBtn.style.position = 'absolute';
    deleteBtn.style.top = '-8px';
    deleteBtn.style.right = '-8px';
    deleteBtn.style.background = '#ef4444';
    deleteBtn.style.color = 'white';
    deleteBtn.style.border = 'none';
    deleteBtn.style.borderRadius = '50%';
    deleteBtn.style.width = '24px';
    deleteBtn.style.height = '24px';
    deleteBtn.style.cursor = 'pointer';
    deleteBtn.style.display = 'flex';
    deleteBtn.style.alignItems = 'center';
    deleteBtn.style.justifyContent = 'center';
    deleteBtn.style.boxShadow = 'var(--shadow-sm)';
    
    deleteBtn.onclick = () => {
      scannedPages.splice(index, 1);
      if (scannedPages.length === 0) {
        // Go back to step 1
        document.getElementById('step2-5-pages').classList.remove('active');
        document.getElementById('step2-5-pages').classList.add('hidden');
        resetUploadModal();
      } else {
        renderPagesPreview();
      }
    };
    
    wrapper.appendChild(img);
    wrapper.appendChild(deleteBtn);
    container.appendChild(wrapper);
  });
  feather.replace();
}

document.getElementById('btnAddMorePages').addEventListener('click', () => {
  document.getElementById('step2-5-pages').classList.remove('active');
  document.getElementById('step2-5-pages').classList.add('hidden');
  step1.classList.remove('hidden');
  step1.classList.add('active');
  modalTitle.textContent = "Upload Paper";
});

document.getElementById('btnContinueToMetadata').addEventListener('click', () => {
  document.getElementById('step2-5-pages').classList.remove('active');
  document.getElementById('step2-5-pages').classList.add('hidden');
  step3.classList.remove('hidden');
  step3.classList.add('active');
  modalTitle.textContent = "Paper Details";
  
  // Show first page in metadata preview
  if (scannedPages.length > 0) {
    finalPreviewImg.src = scannedPages[0];
  }

  // Render or reset CAPTCHA explicitly
  if (typeof turnstile !== 'undefined') {
    if (turnstileWidgetId === null) {
      turnstileWidgetId = turnstile.render('#turnstile-widget', {
        sitekey: '0x4AAAAAAD0ecYMaSbzQmT-a',
        theme: 'auto'
      });
    } else {
      turnstile.reset(turnstileWidgetId);
    }
  }
});

// Edit Image (Go back from Step 3 to Step 2.5)
document.getElementById('btnEditImage').addEventListener('click', () => {
  step3.classList.remove('active');
  step3.classList.add('hidden');
  const step25 = document.getElementById('step2-5-pages');
  step25.classList.remove('hidden');
  step25.classList.add('active');
  modalTitle.textContent = "Scanned Pages";
});

// Finalize Upload
document.getElementById('metadataForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.textContent = "Uploading...";

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());
  
  // CAPTCHA Validation
  const captchaToken = data['cf-turnstile-response'];
  if (!captchaToken) {
    alert("Please complete the CAPTCHA verification.");
    submitBtn.disabled = false;
    submitBtn.textContent = "Finalize Upload";
    return;
  }
  
  if (scannedPages.length === 0) {
    alert("No pages found to upload.");
    submitBtn.disabled = false;
    submitBtn.textContent = "Finalize Upload";
    return;
  }

  // Generate PDF from scannedPages using jsPDF
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  for(let i=0; i<scannedPages.length; i++) {
    if (i > 0) doc.addPage();
    
    // Get image dimensions to scale it properly on A4
    const imgProps = doc.getImageProperties(scannedPages[i]);
    const pdfWidth = doc.internal.pageSize.getWidth();
    // Maintain aspect ratio
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
    
    doc.addImage(scannedPages[i], 'JPEG', 0, 0, pdfWidth, pdfHeight);
  }
  
  const pdfBase64 = doc.output('datauristring');

  data.captcha_token = captchaToken;
  data.image = pdfBase64;
  
  try {
    const response = await fetch('backend/upload.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if(result.status === 'success') {
      alert("Paper uploaded successfully!");
      uploadModal.classList.remove('show');
      resetUploadModal();
      fetchStats(); // Update dashboard instantly
    } else {
      alert("Error: " + result.message);
      // Reset CAPTCHA so user can try again
      if (typeof turnstile !== 'undefined' && turnstileWidgetId !== null) {
        turnstile.reset(turnstileWidgetId);
      }
    }
  } catch(error) {
    console.error(error);
    alert("Failed to connect to the server.");
    if (typeof turnstile !== 'undefined' && turnstileWidgetId !== null) {
      turnstile.reset(turnstileWidgetId);
    }
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = "Finalize Upload";
  }
});

function resetUploadModal() {
  step1.classList.remove('hidden');
  step1.classList.add('active');
  step2.classList.remove('active');
  step2.classList.add('hidden');
  
  const step25 = document.getElementById('step2-5-pages');
  if (step25) {
      step25.classList.remove('active');
      step25.classList.add('hidden');
  }
  
  step3.classList.remove('active');
  step3.classList.add('hidden');
  modalTitle.textContent = "Upload Paper";
  
  scannedPages = [];
  
  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
  imageToCrop.src = "";
  cameraInput.value = "";
  galleryInput.value = "";
  document.getElementById('metadataForm').reset();
  document.getElementById('yearInput').value = new Date().getFullYear();
  document.getElementById('rotationSlider').value = 0;
  currentRotation = 0;
  
  if (typeof turnstile !== 'undefined' && turnstileWidgetId !== null) {
    turnstile.reset(turnstileWidgetId);
  }
}


// --- Download Flow Logic ---
const searchForm = document.getElementById('searchForm');
const searchPaperName = document.getElementById('searchPaperName');
const searchPaperCode = document.getElementById('searchPaperCode');
const searchSemester = document.getElementById('searchSemester');
const btnSearch = document.getElementById('btnSearch');
const searchBtnText = document.getElementById('searchBtnText');
const searchResultsArea = document.getElementById('searchResultsArea');
const resultsGrid = document.getElementById('resultsGrid');

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function checkSearchInputs() {
  const hasInput = searchPaperName.value.trim() !== '' || 
                   searchPaperCode.value.trim() !== '' || 
                   searchSemester.value !== '';
  btnSearch.disabled = !hasInput;
}

const debouncedCheckSearchInputs = debounce(checkSearchInputs, 150);

searchPaperName.addEventListener('input', debouncedCheckSearchInputs);
searchPaperCode.addEventListener('input', debouncedCheckSearchInputs);
searchSemester.addEventListener('change', checkSearchInputs); // change events don't need debounce

searchForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  // UI Loading State
  btnSearch.disabled = true;
  searchBtnText.textContent = "Searching...";
  searchResultsArea.classList.add('hidden');
  resultsGrid.innerHTML = '';
  
  try {
    const params = new URLSearchParams({
      paperName: searchPaperName.value,
      paperCode: searchPaperCode.value,
      semester: searchSemester.value
    });
    
    const response = await fetch(`backend/search.php?${params}`);
    const result = await response.json();
    
    if(result.status === 'success') {
      if(result.data.length === 0) {
         resultsGrid.innerHTML = `<p style="text-align: center; grid-column: 1/-1; color: var(--text-secondary);">No papers found matching your criteria.</p>`;
      } else {
         result.data.forEach(paper => {
           resultsGrid.innerHTML += `
            <div class="result-card">
              <div class="result-image-placeholder" style="background-image: url('backend/uploads/${paper.fileName}'); background-size: cover; background-position: center; border: 1px solid #e2e8f0;">
              </div>
              <div class="result-details">
                <h4 style="margin-bottom: 0.25rem;">${paper.paperName}</h4>
                <p>${paper.paperCode} • ${paper.semester} Semester • ${paper.month} ${paper.year}</p>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem; font-style: italic;">
                  Uploaded by: <strong>${paper.studentName}</strong> (${paper.department}, ${paper.batch})
                </p>
                <a href="backend/download.php?id=${paper.id}" class="btn-secondary download-btn track-download" style="text-decoration:none; display:inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                   <i data-feather="download" style="width:16px;height:16px;"></i> Download Paper
                </a>
              </div>
            </div>
          `;
         });
         
         // Attach click listeners to update stats instantly when download is clicked
         document.querySelectorAll('.track-download').forEach(btn => {
           btn.addEventListener('click', () => {
             // Increment local counter instantly
             const currentDownloads = parseInt(statDownloads.textContent) || 0;
             statDownloads.textContent = currentDownloads + 1;
           });
         });
      }
    }
    searchResultsArea.classList.remove('hidden');
  } catch (error) {
    console.error(error);
    alert("Failed to fetch papers from the server.");
  } finally {
    searchBtnText.textContent = "Search Papers";
    btnSearch.disabled = false;
  }
});

// --- Contact Form Logic ---
const contactForm = document.getElementById('contactForm');
const contactStatus = document.getElementById('contactStatus');
const submitBtn = document.getElementById('contactSubmitBtn');
const btnText = document.getElementById('contactBtnText');

if (contactForm) {
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // UI Loading State
    submitBtn.disabled = true;
    const originalText = btnText.textContent;
    btnText.textContent = "Sending...";
    
    // Hide status
    statusDiv.classList.add('hidden');
    statusDiv.className = 'contact-status hidden';

    const formData = {
      name: document.getElementById('contactName').value,
      email: document.getElementById('contactEmail').value,
      subject: document.getElementById('contactSubject').value,
      message: document.getElementById('contactMessage').value
    };

    try {
      const response = await fetch(contactForm.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });
      
      statusDiv.classList.remove('hidden');
      if (response.ok) {
        statusDiv.classList.add('success');
        statusDiv.textContent = "Your message has been sent successfully.";
        contactForm.reset(); // clear form
        
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
          statusDiv.classList.add('hidden');
        }, 5000);
      } else {
        const data = await response.json();
        statusDiv.classList.add('error');
        if (Object.hasOwn(data, 'errors')) {
          statusDiv.textContent = data["errors"].map(error => error["message"]).join(", ");
        } else {
          statusDiv.textContent = "Oops! There was a problem submitting your form";
        }
      }

    } catch (error) {
      console.error(error);
      statusDiv.classList.remove('hidden');
      statusDiv.classList.add('error');
      statusDiv.textContent = "Failed to send message. Please try again later.";
    } finally {
      submitBtn.disabled = false;
      btnText.textContent = originalText;
    }
  });
}

// --- Back to Top Button ---
const backToTopBtn = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
  if (window.scrollY > 300) {
    backToTopBtn.classList.add('active');
  } else {
    backToTopBtn.classList.remove('active');
  }
});

if (backToTopBtn) {
  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
  imageToCrop.src = "";
  cameraInput.value = "";
  galleryInput.value = "";
  document.getElementById('metadataForm').reset();
  document.getElementById('yearInput').value = new Date().getFullYear();
  document.getElementById('rotationSlider').value = 0;
  currentRotation = 0;
  
  if (typeof turnstile !== 'undefined' && turnstileWidgetId !== null) {
    turnstile.reset(turnstileWidgetId);
  }
}

// --- FAQ Accordion Logic ---
const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
  const question = item.querySelector('.faq-question');
  if(question) {
    question.addEventListener('click', () => {
      const isActive = item.classList.contains('active');
      
      // Close all items
      faqItems.forEach(otherItem => {
        otherItem.classList.remove('active');
        const otherAnswer = otherItem.querySelector('.faq-answer');
        if (otherAnswer) {
          otherAnswer.style.maxHeight = null;
        }
      });
      
      // If it wasn't active, open it
      if (!isActive) {
        item.classList.add('active');
        const answer = item.querySelector('.faq-answer');
        if (answer) {
          answer.style.maxHeight = answer.scrollHeight + "px";
        }
      }
    });
  }
});

// --- Admin Panel Logic ---
const openAdminLoginModal = document.getElementById('openAdminLoginModal');
const adminLoginModal = document.getElementById('adminLoginModal');
const closeAdminLoginModal = document.getElementById('closeAdminLoginModal');
const btnAdminLogin = document.getElementById('btnAdminLogin');
const adminPassword = document.getElementById('adminPassword');

const adminDashboardModal = document.getElementById('adminDashboardModal');
const closeAdminDashboardModal = document.getElementById('closeAdminDashboardModal');
const btnAdminLogout = document.getElementById('btnAdminLogout');
const btnAdminRefresh = document.getElementById('btnAdminRefresh');
const adminPendingList = document.getElementById('adminPendingList');

// Open Login Modal
if(openAdminLoginModal) {
  openAdminLoginModal.addEventListener('click', (e) => {
    e.preventDefault();
    adminLoginModal.classList.add('show');
  });
}

// Close Login Modal
if(closeAdminLoginModal) {
  closeAdminLoginModal.addEventListener('click', () => {
    adminLoginModal.classList.remove('show');
  });
}

// Close Dashboard Modal
if(closeAdminDashboardModal) {
  closeAdminDashboardModal.addEventListener('click', () => {
    adminDashboardModal.classList.remove('show');
  });
}

// Handle Login
if(btnAdminLogin) {
  btnAdminLogin.addEventListener('click', async () => {
    const pwd = adminPassword.value;
    if(!pwd) return alert('Enter password');
    
    btnAdminLogin.textContent = 'Authenticating...';
    try {
      const res = await fetch('backend/admin_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: pwd })
      });
      const data = await res.json();
      
      if(data.status === 'success') {
        adminPassword.value = '';
        adminLoginModal.classList.remove('show');
        loadPendingPapers();
        adminDashboardModal.classList.add('show');
      } else {
        alert(data.message);
      }
    } catch(err) {
      alert('Network error');
    }
    btnAdminLogin.textContent = 'Authenticate';
  });
}

// Handle Logout
if(btnAdminLogout) {
  btnAdminLogout.addEventListener('click', async () => {
    await fetch('backend/admin_logout.php');
    adminDashboardModal.classList.remove('show');
    alert('Logged out successfully.');
  });
}

// Handle Refresh
if(btnAdminRefresh) {
  btnAdminRefresh.addEventListener('click', loadPendingPapers);
}

// Fetch and render pending papers
async function loadPendingPapers() {
  adminPendingList.innerHTML = '<div style="text-align:center; padding: 2rem;">Loading...</div>';
  try {
    const res = await fetch('backend/get_pending.php');
    const data = await res.json();
    
    if(data.status !== 'success') {
      adminPendingList.innerHTML = `<div style="text-align:center; color:red; padding:2rem;">${data.message}</div>`;
      return;
    }
    
    const papers = data.data;
    if(papers.length === 0) {
      adminPendingList.innerHTML = `
        <div style="text-align:center; padding: 4rem; color: var(--text-secondary);">
          <i data-feather="check-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; color: var(--success);"></i>
          <h3>All caught up!</h3>
          <p>There are no pending papers to moderate.</p>
        </div>
      `;
      feather.replace();
      return;
    }
    
    adminPendingList.innerHTML = '';
    papers.forEach(p => {
      const div = document.createElement('div');
      div.style.cssText = 'background: var(--card-bg); padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--glass-border); flex-wrap: wrap; gap: 1rem;';
      
      div.innerHTML = `
        <div>
          <h3 style="margin-bottom: 0.25rem; font-size: 1.1rem; color: var(--text-primary);">${p.paperName} (${p.year})</h3>
          <p style="color: var(--text-secondary); font-size: 0.9rem;">${p.department} | Sem: ${p.semester} | By: ${p.studentName}</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <a href="backend/uploads/${p.fileName}" target="_blank" class="btn-primary" style="padding: 0.5rem 1rem; text-decoration:none; display:flex; align-items:center; gap:0.25rem;"><i data-feather="eye"></i> View</a>
          <button onclick="handleAdminAction(${p.id}, 'approve')" style="background: var(--success); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; display:flex; align-items:center; gap:0.25rem; font-weight: 500;"><i data-feather="check"></i> Approve</button>
          <button onclick="handleAdminAction(${p.id}, 'reject')" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; display:flex; align-items:center; gap:0.25rem; font-weight: 500;"><i data-feather="x"></i> Reject</button>
        </div>
      `;
      adminPendingList.appendChild(div);
    });
    feather.replace();
    
  } catch(err) {
    adminPendingList.innerHTML = '<div style="text-align:center; color:red; padding:2rem;">Failed to load papers.</div>';
  }
}

// Handle Approve/Reject Action
window.handleAdminAction = async function(id, action) {
  if(action === 'reject' && !confirm('Permanently delete this paper?')) return;
  
  try {
    const res = await fetch('backend/admin_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}&action=${action}`
    });
    const data = await res.json();
    
    if(data.status === 'success') {
      loadPendingPapers(); // Reload list
      fetchStats(); // Update dashboard counts
    } else {
      alert(data.message);
    }
  } catch(err) {
    alert('Network Error');
  }
};


