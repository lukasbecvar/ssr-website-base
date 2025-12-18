/* visitor online status updater */ 
document.addEventListener('DOMContentLoaded', function() {
    function sendOnlineStatus() {
        fetch('/api/visitor/update/activity', {
            method: 'POST'
        })
    }

    // send online status every 5 minutes
    setInterval(sendOnlineStatus, 300000)

    // init function
    sendOnlineStatus()
})
