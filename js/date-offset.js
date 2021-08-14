/*
This script is calculating the references by local browser's time.
*/

document.addEventListener("DOMContentLoaded", function (event) {

    for (let dateField of document.querySelectorAll('[data-iso-timestamp]')) {
        const date = new Date(dateField.getAttribute('data-iso-timestamp'));
        //Date must be valid, 'data-timestamp-meticulousness' must exist
        if (isNaN(date.getMonth()) || !dateField.hasAttribute('data-timestamp-meticulousness')) continue;
        const meticulousness = ["YEARS", "MONTHS", "DAYS", "HOURS", "MINUTES", "SECONDS"].indexOf(dateField.getAttribute('data-timestamp-meticulousness'));

        if (meticulousness >= 0) {
            //Adding leading zero to month, day, hour and minute but only keeping the last two numbers
            let outputDate = date.getFullYear().toString().slice(2);
            if (meticulousness >= 2) outputDate = (("0" + (date.getMonth() + 1)).slice(-2) + '.').concat(outputDate);
            if (meticulousness >= 1) outputDate = (("0" + date.getDate()).slice(-2) + '.').concat(outputDate);
            //Date month is indexed -> 0 represents january.
            //u00a0 represents &nbsp;
            if (meticulousness >= 3) outputDate = outputDate.concat("\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2));
            if (meticulousness === 3) outputDate = outputDate.concat(":00");
            if (meticulousness >= 4) outputDate = outputDate.concat(':' + ("0" + date.getMinutes()).slice(-2));
            if (meticulousness >= 5) outputDate = outputDate.concat(':' + ("0" + date.getSeconds()).slice(-2));

            dateField.textContent = outputDate;
        }
    }
});
