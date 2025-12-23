// Ümumi JavaScript funksiyaları

// Bootstrap tooltip funksiyasını aktiv etmək
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // İmtahan formu üçün təsdiqləmə
    var examForm = document.getElementById('examForm');
    if (examForm) {
        // Formun göndərilməsini əngəlləmə
        var isSubmitting = false;
        
        examForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            
            // İmtahanın bitməsini təsdiqləmə
            var confirmed = confirm('İmtahanı bitirmək istədiyinizə əminsiniz?');
            if (!confirmed) {
                e.preventDefault();
            } else {
                isSubmitting = true;
            }
        });
    }
    
    // Status filtrini dəyişdikdə formu göndərmək
    var statusFilter = document.getElementById('status');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Sual növü seçilən zaman sahələri göstərmək
    var questionType = document.getElementById('question_type');
    if (questionType) {
        toggleQuestionFields();
    }
    
    // İstifadəçi əlavə etmə modalında qrup sahəsini göstərmək/gizlətmək
    var userStatus = document.getElementById('status');
    if (userStatus) {
        toggleGroupField();
    }
});

// Sual növünə görə lazım olan sahələri göstərmək
function toggleQuestionFields() {
    var questionType = document.getElementById('question_type');
    if (!questionType) return;
    
    var selectedOption = questionType.options[questionType.selectedIndex];
    
    if (!selectedOption.value) {
        hideElement('multiple_fields');
        hideElement('open_fields');
        hideElement('matching_fields');
        return;
    }
    
    var questionVar = selectedOption.getAttribute('data-var');
    
    hideElement('multiple_fields');
    hideElement('open_fields');
    hideElement('matching_fields');
    
    if (questionVar === 'multiple') {
        showElement('multiple_fields');
    } else if (questionVar === 'open') {
        showElement('open_fields');
    } else if (questionVar === 'matching') {
        showElement('matching_fields');
    }
}

// İstifadəçi əlavə etmə modal-da qrup sahəsini göstərmək/gizlətmək
function toggleGroupField() {
    var status = document.getElementById('status');
    if (!status) return;
    
    var groupField = document.getElementById('groupField');
    if (!groupField) return;
    
    if (status.value === 'admin') {
        hideElement('groupField');
        var groupSelect = document.getElementById('group_id');
        if (groupSelect) {
            groupSelect.value = '';
        }
    } else {
        showElement('groupField');
    }
}

// Elementi gizlətmək
function hideElement(id) {
    var element = document.getElementById(id);
    if (element) {
        element.style.display = 'none';
    }
}

// Elementi göstərmək
function showElement(id) {
    var element = document.getElementById(id);
    if (element) {
        element.style.display = 'block';
    }
}

// İmtahan vaxt sayğacı
function startTimer(duration, displayElement) {
    var timer = duration;
    var minutes, seconds;
    
    var countDown = setInterval(function() {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        
        displayElement.textContent = minutes + ":" + seconds;
        
        if (--timer < 0) {
            clearInterval(countDown);
            displayElement.textContent = "Vaxt bitdi!";
            document.getElementById('examForm').submit();
        }
    }, 1000);
}

// Select elementlərinə axtarış funksiyası əlavə etmək
function makeSearchableSelect(selectId) {
    var select = document.getElementById(selectId);
    if (!select) return;
    
    // Select2 kitabxanası varsa
    if (typeof $.fn.select2 !== 'undefined') {
        $('#' + selectId).select2({
            placeholder: "Seçin",
            allowClear: true
        });
    }
}