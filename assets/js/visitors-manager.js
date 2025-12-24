document.addEventListener('DOMContentLoaded', function() {
    const timePeriodSelect = document.getElementById('time-period')
    
    // time period selection logic
    if (timePeriodSelect) {
        // update time period in url
        timePeriodSelect.addEventListener('change', function() {
            const selectedValue = this.value
            window.location.href = `/admin/visitors/metrics?time_period=${selectedValue}`
        })

        // select selecton value from url
        const urlParams = new URLSearchParams(window.location.search)
        const timePeriod = urlParams.get('time_period')

        // set selected value
        if (timePeriod) {
            timePeriodSelect.value = timePeriod
        } else {
            timePeriodSelect.value = 'last_week'
        }
    }

    // line selector highlight logic
    const hash = window.location.hash
    if (hash && hash.startsWith('#')) {
        const id = hash.substring(1)
        const row = document.getElementById(id)

        if (row) {
            const thElements = row.getElementsByTagName('th')
            for (let j = 0; j < thElements.length; j++) {
                thElements[j].classList.add('highlighter')
            }
        }
    }
})
