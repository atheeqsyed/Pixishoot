$(document).ready(function () {
    let currentEventId = null;
    let selectedDate = null;

    const TIME_SLOTS = [
        '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM',
        '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM'
    ];

    const CALENDAR_API_URL = '/pixishoot1/api/';
    const EVENT_API_URL = '/pixishoot1/api/getEvents.php';

    function closeModal() {
        console.log('Closing modal...');
        $('#overlay').fadeOut();
        $('#eventModal').fadeOut();
        resetModalFields();
    }

    function resetModalFields() {
        $('#brandName').val('');
        $('#serviceSelect').val('On Model');
        $('#eventDuration').val('');
        $('#submitEventBtn').show();
        $('#updateEventBtn').hide();
        $('#deleteEventBtn').hide();
    }

    function populateStartTimeDropdown() {
        const $startTimeDropdown = $('#startTime');
        $startTimeDropdown.empty();
        TIME_SLOTS.forEach(function (time) {
            const option = new Option(time, time);
            $startTimeDropdown.append(option);
        });
    }

    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', error);
        alert('An error occurred while processing your request.');
    }

    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        events: function (start, end, timezone, callback) {
            const serviceFilter = $('#serviceFilter').val();
            console.log('Service Filter:', serviceFilter);
            $.ajax({
                url: EVENT_API_URL,
                method: 'GET',
                dataType: 'json',
                data: {
                    start: start.format(),
                    end: end.format(),
                    service: serviceFilter
                },
                success: function (events) {
                    if (!Array.isArray(events)) {
                        console.error('Invalid event data:', events);
                        alert('No events found.');
                        return;
                    }
                    callback(events);
                },
                error: handleAjaxError
            });
        },

        dayClick: function (date) {
            const currentDate = moment();
            if (date.isSameOrBefore(currentDate, 'day') || date.isoWeekday() === 7) {
                alert('Events cannot be created on past dates or Sundays.');
                return;
            }
            currentEventId = null;
            selectedDate = date;
            openModal(date);

            $('#brandName').prop('readonly', false);
            $('#serviceSelect').prop('readonly', false);
            resetModalFields();
        },

        eventRender: function (event, element) {
            const colors = {
                'On Model': '#34495e',
                'Ghost Mannequin': '#f39c12',
                'Product Photography': '#1abc9c'
            };
            element.css('background-color', colors[event.service] || '#95a5a6');
            element.attr('title', `${event.title} (${event.service})`);

            if (moment(event.start).isBefore(moment(), 'day')) {
                element.addClass('disabled-event');
                event.editable = false;
            }

            element.find('.delete-btn').remove();
            element.on('click', function () {
                currentEventId = event.id;
                $('#deleteEventBtn').show();
                openModalForEditing(event);
            });
        },

        eventClick: function (event) {
            currentEventId = event.id;
            $('#modalHeader').text('Edit Event');
            const eventDuration = moment.duration(moment(event.end).diff(moment(event.start))).asHours();
            const startTime = moment(event.start).format('hh:mm A');

            $('#brandName').val(event.title).prop('readonly', true);
            $('#serviceSelect').val(event.service).prop('readonly', true);
            $('#eventDuration').val(eventDuration);

            populateStartTimeDropdown();
            $('#startTime').val(startTime);

            $('#submitEventBtn').hide();
            $('#updateEventBtn').show();
            $('#deleteEventBtn').show();
            openModal();
        },

        editable: true,
        droppable: true,
        eventDrop: function (event, delta, revertFunc) {
            const newDate = event.start;
            const currentDate = moment();

            if (newDate.isSameOrBefore(currentDate, 'day') || newDate.isoWeekday() === 7) {
                alert('Events cannot be moved to past dates or Sundays.');
                revertFunc();
                return;
            }

            const startTime = newDate.format('HH:mm:ss');
            const endTime = moment(newDate).add(moment.duration(moment(event.end).diff(moment(event.start)))).format('HH:mm:ss');
            const dateString = newDate.format('YYYY-MM-DD');

            $.ajax({
                url: `${CALENDAR_API_URL}checkEventAvailability.php`,
                method: 'POST',
                dataType: 'json',
                data: {
                    date: dateString,
                    startTime: startTime,
                    endTime: endTime
                },
                success: function (response) {
                    if (response.success) {
                        $.ajax({
                            url: `${CALENDAR_API_URL}updateEventDate.php`,
                            method: 'POST',
                            data: {
                                id: event.id,
                                startTime: startTime,
                                endTime: endTime
                            },
                            success: function (updateResponse) {
                                if (updateResponse.success) {
                                    alert('Event date updated successfully!');
                                    $('#calendar').fullCalendar('refetchEvents');
                                } else {
                                    alert('Error updating event date: ' + updateResponse.message);
                                    revertFunc();
                                }
                            },
                            error: handleAjaxError
                        });
                    } else {
                        alert('The selected time slot is already booked. Please choose another time.');
                        revertFunc();
                    }
                },
                error: handleAjaxError
            });
        }
    });

    $('#updateEventBtn').on('click', function () {
        // Retrieve the values from the fields
        const title = ($('#brandName').val() || '').trim();
        const service = $('#serviceSelect').val().trim();

        // Check if selectedDate is defined and retrieve its value, or fallback to #datePicker
        const date = selectedDate ? selectedDate.format('YYYY-MM-DD') : ($('#datePicker').val() || '').trim();

        const duration = parseFloat($('#eventDuration').val().trim());
        const startTime = $('#startTime').val().trim();

        console.log('Fields before validation:', { title, service, date, duration, startTime });

        // Ensure the required fields are filled out and valid
        if (!title || !service || !date || !startTime || isNaN(duration) || duration <= 0) {
            console.log('Validation failed: ', { title, service, date, startTime, duration });
            alert('Please fill in all fields correctly.');
            return;
        }

        // Ensure the start time is valid and formatted correctly (12-hour format to 24-hour format)
        const startMoment = moment(`${date} ${startTime}`, 'YYYY-MM-DD hh:mm A');
        if (!startMoment.isValid()) {
            console.log('Invalid start time:', startTime);
            alert('Invalid start time format.');
            return;
        }

        // Calculate the end time based on the duration
        const endMoment = startMoment.clone().add(duration, 'hours');
        const endTime = endMoment.format('HH:mm:ss'); // 24-hour format for end time

        // Log the data that will be sent to the backend
        console.log('Sending data in AJAX:', {
            id: currentEventId,
            title: title,
            service: service,
            date: date,
            startTime: startMoment.format('HH:mm:ss'),
            endTime: endTime
        });

        // Proceed with the AJAX request only if validation is passed
        $.ajax({
            url: '/pixishoot1/api/updateEventDate.php',
            method: 'POST',
            data: {
                id: currentEventId,
                title: title,
                service: service,
                date: date,
                startTime: startMoment.format('HH:mm:ss'),
                endTime: endTime
            },
            success: function (response) {
                if (response.success) {
                    alert('Event updated successfully!');
                    $('#calendar').fullCalendar('refetchEvents');
                    closeModal();
                } else {
                    alert('Error updating event: ' + response.message);
                }
            },
            error: function (xhr) {
                console.error('Error occurred:', xhr.responseText);
                alert('An error occurred while updating the event.');
            }
        });
    });






    $('#submitEventBtn').on('click', function () {
        const title = $('#brandName').val().trim();
        const service = $('#serviceSelect').val().trim();
        const date = selectedDate ? selectedDate.format('YYYY-MM-DD') : $('#datePicker').val();
        const duration = parseFloat($('#eventDuration').val().trim());
        const startTime = $('#startTime').val().trim();

        if (!title || !service || !date || !startTime || isNaN(duration) || duration <= 0) {
            alert('Please fill in all fields correctly.');
            return;
        }

        const startMoment = moment(`${date} ${startTime}`, 'YYYY-MM-DD hh:mm A');
        if (!startMoment.isValid()) {
            alert('Invalid start time format.');
            return;
        }

        const endMoment = startMoment.clone().add(duration, 'hours');
        const endTime = endMoment.format('HH:mm:ss');

        $.ajax({
            url: `${CALENDAR_API_URL}createEvent.php`,
            method: 'POST',
            data: {
                title: title,
                service: service,
                date: date,
                startTime: startMoment.format('HH:mm:ss'),
                duration: duration
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    $('#calendar').fullCalendar('refetchEvents');
                    closeModal();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    });

    $('#serviceFilter').on('change', function () {
        $('#calendar').fullCalendar('refetchEvents');
    });

    $('#eventDuration, #datePicker').on('change', function () {
        const duration = parseInt($('#eventDuration').val(), 10);
        const date = selectedDate ? selectedDate.format('YYYY-MM-DD') : $('#datePicker').val();

        if (!duration || !date) {
            $('#startTime').empty().append('<option value="">Please select duration and date</option>');
            return;
        }

        $.ajax({
            url: `${CALENDAR_API_URL}getAvailableSlots.php`,
            method: 'POST',
            data: { date, duration },
            success: function (response) {
                if (response.success) {
                    if (Array.isArray(response.slots) && response.slots.length > 0) {
                        const options = response.slots.map(slot =>
                            `<option value="${slot.start}-${slot.end}">${slot.display}</option>`
                        );
                        $('#startTime').html(options.join(''));
                    } else {
                        alert('No slots available for the selected date and duration.');
                        $('#startTime').empty().append('<option value="">No available slots</option>');
                    }
                } else {
                    alert('Error fetching slots: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    });
});

// Utility function to format 24-hour time to 12-hour AM/PM format
function convertTo12HourFormat(time) {
    const [hours, minutes] = time.split(':');
    const isPM = parseInt(hours) >= 12;
    const formattedHour = (parseInt(hours) % 12 || 12);  // Convert to 12-hour format
    const ampm = isPM ? 'PM' : 'AM';
    return `${formattedHour}:${minutes} ${ampm}`;
}

// Handle event deletion with modal confirmation
$('#deleteEventBtn').on('click', function () {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }

    $.ajax({
        url: '/pixishoot1/api/deleteEvent.php',
        method: 'POST',
        data: { id: currentEventId },
        success: function (response) {
            try {
                let jsonResponse = typeof response === 'string' ? JSON.parse(response) : response;
                if (jsonResponse.success) {
                    alert('Event deleted successfully!');
                    $('#calendar').fullCalendar('refetchEvents');
                    closeModal();
                } else {
                    alert('Error deleting event: ' + jsonResponse.message);
                }
            } catch (e) {
                console.error('Unexpected server response:', response);
                alert('Invalid server response.');
            }
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            alert('An error occurred while deleting the event.');
        }
    });
});

// Open modal for creating or editing an event
function openModal(date) {
    $('#overlay').fadeIn();
    $('#eventModal').fadeIn();
    if (date) {
        $('#modalHeader').text('Create New Event on ' + date.format('YYYY-MM-DD'));
        resetModalFields();
    } else {
        $('#deleteEventBtn').show();
    }

    $('#brandName').prop('readonly', false); // Ensure it is editable
    $('#serviceSelect').prop('readonly', false); // Ensure it is editable
}

// Open modal for editing an event
function openModalForEditing(event) {
    console.log('Opening modal for editing event:', event);
    $('#modalHeader').text('Edit Event');
    $('#brandName').val(event.title).prop('readonly', true);  // Make brand name field read-only
    $('#serviceSelect').val(event.service).prop('readonly', true); // Make service field read-only
    $('#eventDuration').val(moment.duration(moment(event.end).diff(moment(event.start))).asHours());
    $('#startTime').val(moment(event.start).format('hh:mm A')); // Populate start time in 12-hour format

    $('#submitEventBtn').hide();
    $('#updateEventBtn').show();
    $('#deleteEventBtn').show();
    openModal();
}

// Handle record fetching with filters or today's date
function fetchRecords(service, startDate, endDate) {
    console.log("Fetching records with parameters:", { service, startDate, endDate });
    $('#loadingSpinner').show();

    $.ajax({
        url: '/pixishoot1/api/fetchEventRecords.php',
        method: 'GET',
        data: { service, start_date: startDate, end_date: endDate },
        success: function (response) {
            $('#loadingSpinner').hide();
            if (response.error) {
                alert(response.error);
                return;
            }

            updateRecordsTable(response);
        },
        error: function () {
            alert('Error fetching records. Please try again later.');
            $('#loadingSpinner').hide();
        }
    });
}

// Update the records table with the fetched data
function updateRecordsTable(records) {
    let tableBody = $('#recordsTable tbody');
    tableBody.empty();

    if (records.length === 0) {
        tableBody.append('<tr><td colspan="6">No records found.</td></tr>');
        return;
    }

    records.forEach(function (record) {
        const formattedStartTime = convertTo12HourFormat(record.start_time);
        const formattedEndTime = convertTo12HourFormat(record.end_time);

        tableBody.append(`
                <tr data-id="${record.id}">
                    <td>${record.title || 'N/A'}</td>
                    <td>${record.service}</td>
                    <td>${moment(record.date).format('MMM D, YYYY')}</td>
                    <td>${formattedStartTime}</td>
                    <td>${formattedEndTime}</td>
                    <td><button class="delete-btn">Delete</button></td>
                </tr>
            `);
    });

    if ($.fn.dataTable.isDataTable('#recordsTable')) {
        $('#recordsTable').DataTable().clear().destroy();
    }

    $('#recordsTable').DataTable({
        paging: true,
        ordering: true,
        order: [[2, 'asc']],
        columnDefs: [{
            targets: [2],
            render: function (data) {
                return moment(data).format('MMM D, YYYY');
            }
        }],
    });

    $('.delete-btn').click(function () {
        let row = $(this).closest('tr');
        let recordId = row.data('id');
        deleteRecord(recordId, row);
    });
}

// Handle record deletion
function deleteRecord(recordId, row) {
    console.log("Deleting record with ID:", recordId);

    $.ajax({
        url: '/pixishoot1/api/deleteEventRecord.php',
        method: 'POST',
        data: { id: recordId },
        success: function (response) {
            if (response.error) {
                alert(response.error);
                return;
            }

            row.remove();  // Remove the row from the table
        },
        error: function () {
            alert('Error deleting record. Please try again.');
        }
    });
}

// Handle the "View Records" button click event
$('#viewRecordsBtn').click(function () {
    let serviceFilter = $('#serviceModalFilter').val();
    let selectedDate = $('#dateModalFilter').val() || moment().format('YYYY-MM-DD');
    fetchRecords(serviceFilter, selectedDate, selectedDate);
    $('#recordsModal').fadeIn();
    $('#overlay').fadeIn();
});

// Handle changes in the service and date filters
$('#serviceModalFilter, #dateModalFilter').change(function () {
    let serviceFilter = $('#serviceModalFilter').val();
    let selectedDate = $('#dateModalFilter').val() || moment().format('YYYY-MM-DD');
    fetchRecords(serviceFilter, selectedDate, selectedDate);
});

// Handle PDF export
$('#exportToPdfBtn').click(function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const selectedService = $('#serviceSelect').val() || "ALL";
    const selectedDate = $('#datePicker').val() || new Date().toLocaleDateString();

    doc.setFontSize(24);
    doc.setFont('helvetica', 'bold');
    doc.text('PIXISHOOT CALENDAR SCHEDULING SYSTEM', 14, 20);

    doc.setFontSize(14);
    doc.setFont('helvetica', 'normal');
    doc.text('Event Records Report', 14, 30);
    doc.text(`Selected Service: ${selectedService}`, 14, 40);
    doc.text(`Selected Date: ${selectedDate}`, 14, 50);

    doc.setDrawColor(44, 62, 80);
    doc.line(14, 52, 200, 52); // Line under the title

    const formattedRows = [];
    $('#recordsTable tbody tr').each(function () {
        const row = [];
        const $cells = $(this).find('td');

        const startTime = $cells.eq(3).text().trim();
        const endTime = $cells.eq(4).text().trim();

        row.push(
            $cells.eq(0).text().trim(),
            $cells.eq(1).text().trim(),
            $cells.eq(2).text().trim(),
            startTime,
            endTime
        );

        formattedRows.push(row);
    });

    doc.autoTable({
        head: [['Brand Name', 'Service', 'Date', 'Start Time', 'End Time']],
        body: formattedRows,
        startY: 60,
        margin: { horizontal: 14 },
        styles: {
            font: 'helvetica',
            fontSize: 9,
            cellPadding: 5,
            overflow: 'linebreak',
            lineColor: [44, 62, 80],
            lineWidth: 0.2,
            halign: 'center',
            valign: 'middle',
            textColor: [44, 62, 80]
        },
        headStyles: {
            fillColor: [44, 62, 80],
            textColor: [255, 255, 255],
            fontSize: 10,
            fontStyle: 'bold',
            halign: 'center'
        },
        alternateRowStyles: {
            fillColor: [240, 240, 240]
        },
        columnStyles: {
            0: { cellWidth: 50 },
            1: { cellWidth: 35 },
            2: { cellWidth: 32 },
            3: { cellWidth: 32 },
            4: { cellWidth: 32 }
        },
        didDrawCell: function (data) {
            if (data.column.index === 5) {  // Skip the "Actions" column
                data.cell.styles.hidden = true;
            }
        }
    });

    const pageCount = doc.internal.getNumberOfPages();
    doc.setFontSize(8);
    doc.text(`Page ${pageCount}`, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
    doc.save('records.pdf');
});

// Close the modal when clicking the close button
$('.close-btn').click(function () {
    $('#recordsModal').fadeOut();
    $('#overlay').fadeOut();
});