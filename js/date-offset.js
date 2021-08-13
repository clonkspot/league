/*
This script is calculating the references by local browser's time.
*/

document.addEventListener("DOMContentLoaded", function(event) {
    for (let dateField of document.getElementsByClassName('date_field')) {
        if (dateField.textContent == null) continue;
        //u00a0 represents &nbsp;
        const inputArray = dateField.textContent.replace(/\u00a0-\u00a0/, '.').replace(' ', '').split(/[-.:]/);
        //Date month is indexed -> 0 represents january. Converting offset to milliseconds.
        if (inputArray.length === 5) {
            //Format for e.g. game list "12.08.21 - 17:24"
            const date = new Date(Date.UTC(20 + inputArray[2], inputArray[1] - 1, inputArray[0], inputArray[3], inputArray[4]));
            //Adding leading zero to month, day, hour and minute but only keeping the last two numbers
            dateField.textContent = ("0" + date.getDate()).slice(-2) + '.' + ("0" + (date.getMonth() + 1)).slice(-2) + '.' + date.getFullYear().toString().slice(2) + "\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2) + ':' + ("0" + date.getMinutes()).slice(-2);
        } else if (inputArray.length === 6) {
            //Format for e.g. league detail view "12.08.21 - 17:24:26"
            const date = new Date(Date.UTC(20 + inputArray[2], inputArray[1] - 1, inputArray[0], inputArray[3], inputArray[4], inputArray[5]));
            //Adding leading zero to month, day, hour, minute and second but only keeping the last two numbers
            dateField.textContent = ("0" + date.getDate()).slice(-2) + '.' + ("0" + (date.getMonth() + 1)).slice(-2) + '.' + date.getFullYear().toString().slice(2) + "\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2) + ':' + ("0" + date.getMinutes()).slice(-2) + ':' + ("0" + date.getSeconds()).slice(-2);
        }
    }
});
