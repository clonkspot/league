/*
This script is calculating the references by local browser's time.
*/

document.addEventListener("DOMContentLoaded", function (event) {

    for (let dateField of document.getElementsByClassName('date_field')) {
        // 'data-iso-timestamp' and 'data-timestamp-precision' must exist
        if (dateField.dataset.isoTimestamp === undefined || dateField.dataset.timestampPrecision === undefined) continue;
        const date = new Date(dateField.dataset.isoTimestamp);
        //Date must be valid
        if (isNaN(date.getMonth())) continue;
        const precision = ["YEARS", "MONTHS", "DAYS", "HOURS", "MINUTES", "SECONDS"].indexOf(dateField.dataset.timestampPrecision);

        if (precision >= 0) {
            //Adding leading zero to month, day, hour and minute but only keeping the last two numbers
            let outputDate = date.getFullYear().toString().slice(2);
            if (precision >= 2) outputDate = (("0" + (date.getMonth() + 1)).slice(-2) + '.').concat(outputDate);
            if (precision >= 1) outputDate = (("0" + date.getDate()).slice(-2) + '.').concat(outputDate);
            //Date month is indexed -> 0 represents january.
            //u00a0 represents &nbsp;
            if (precision >= 3) outputDate += "\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2);
            if (precision === 3) outputDate += ":00";
            if (precision >= 4) outputDate += ':' + ("0" + date.getMinutes()).slice(-2);
            if (precision >= 5) outputDate += ':' + ("0" + date.getSeconds()).slice(-2);

            dateField.textContent = outputDate;
        }
    }
});
