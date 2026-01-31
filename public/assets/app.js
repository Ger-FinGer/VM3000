document.addEventListener('DOMContentLoaded', () => {
    const homeroomSelect = document.getElementById('homeroom-select');
    const homeroomClass = document.getElementById('homeroom-class');
    if (homeroomSelect && homeroomClass) {
        const toggle = () => {
            homeroomClass.style.display = homeroomSelect.value === 'yes' ? 'block' : 'none';
        };
        homeroomSelect.addEventListener('change', toggle);
        toggle();
    }

    const dashboard = document.getElementById('teacher-dashboard');
    if (!dashboard) {
        return;
    }

    const csrfToken = dashboard.dataset.csrf || '';
    const days = JSON.parse(dashboard.dataset.days || '[]');
    const classes = JSON.parse(dashboard.dataset.classes || '[]');
    const periods = Number(dashboard.dataset.periods || 0);

    const myTableBody = document.querySelector('#my-timetable-table tbody');
    const classTableBody = document.querySelector('#class-view-table tbody');
    const classSelect = document.getElementById('class-select');

    const modalElement = document.getElementById('slot-details-modal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

    const slotPlanText = document.getElementById('slot-plan-text');
    const slotRoomSelect = document.getElementById('slot-room-select');
    const slotParticipants = document.getElementById('slot-participants');
    const slotSave = document.getElementById('slot-save');
    const slotDelete = document.getElementById('slot-delete');
    const slotJoin = document.getElementById('slot-join');
    const slotAbsenceSlot = document.getElementById('slot-absence-slot');
    const slotAbsenceDay = document.getElementById('slot-absence-day');
    const slotTrip = document.getElementById('slot-trip');
    const slotTitle = document.getElementById('slot-details-title');
    const slotTypeSummary = document.getElementById('slot-type-summary');

    let activeSlot = null;

    const fetchJson = async (url, payload = {}) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error(text);
            alert('API returned non-JSON response. Please check the server logs.');
            throw error;
        }
    };

    const buildMyTimetable = async () => {
        if (!periods) {
            return;
        }
        const data = await fetchJson('/api/my_timetable.php', { csrf: csrfToken });
        if (!data.ok) {
            alert(data.error || 'Fehler beim Laden.');
            return;
        }

        const bookingMap = {};
        data.bookings.forEach((booking) => {
            bookingMap[booking.day] = bookingMap[booking.day] || {};
            bookingMap[booking.day][booking.period] = booking;
        });

        myTableBody.innerHTML = '';
        for (let period = 1; period <= periods; period += 1) {
            const row = document.createElement('tr');
            const periodCell = document.createElement('td');
            periodCell.textContent = period;
            row.appendChild(periodCell);

            days.forEach((day) => {
                const cell = document.createElement('td');
                const booking = bookingMap[day.id]?.[period];
                if (booking) {
                    cell.classList.add('slot', 'slot-mine');
                    cell.dataset.action = 'slot-details';
                    cell.dataset.classId = booking.class_id;
                    cell.dataset.className = booking.class_name;
                    cell.dataset.day = day.id;
                    cell.dataset.period = period;
                    cell.dataset.bookingId = booking.booking_id;

                    const title = document.createElement('div');
                    title.innerHTML = `<strong>${booking.class_name}</strong>`;
                    cell.appendChild(title);

                    if (booking.is_trip) {
                        const tripBadge = document.createElement('div');
                        tripBadge.className = 'badge bg-danger mt-1';
                        tripBadge.textContent = 'Klassenfahrt';
                        cell.appendChild(tripBadge);
                    }

                    if (booking.room_name) {
                        const roomBadge = document.createElement('div');
                        roomBadge.className = 'badge bg-info text-dark mt-1';
                        roomBadge.textContent = `Raum ${booking.room_name}`;
                        cell.appendChild(roomBadge);
                    }

                    if (booking.plan_text) {
                        const plan = document.createElement('div');
                        plan.className = 'small text-muted mt-1';
                        plan.textContent = booking.plan_text;
                        cell.appendChild(plan);
                    }
                } else {
                    cell.classList.add('slot');
                }
                row.appendChild(cell);
            });

            myTableBody.appendChild(row);
        }
    };

    const buildClassMatrix = async (classId) => {
        classTableBody.innerHTML = '';
        if (!classId || !periods) {
            return;
        }
        const data = await fetchJson('/api/book.php', {
            csrf: csrfToken,
            action: 'matrix',
            class_id: classId,
        });
        if (!data.ok) {
            alert(data.error || 'Fehler beim Laden.');
            return;
        }

        const slotMap = {};
        data.slots.forEach((slot) => {
            slotMap[slot.day] = slotMap[slot.day] || {};
            slotMap[slot.day][slot.period] = slot;
        });

        for (let period = 1; period <= periods; period += 1) {
            const row = document.createElement('tr');
            const periodCell = document.createElement('td');
            periodCell.textContent = period;
            row.appendChild(periodCell);

            days.forEach((day) => {
                const slot = slotMap[day.id]?.[period];
                const cell = document.createElement('td');
                cell.classList.add('slot');
                cell.dataset.day = day.id;
                cell.dataset.period = period;
                cell.dataset.classId = classId;
                cell.dataset.className = classes.find((item) => Number(item.id) === Number(classId))?.name || '';

                if (slot) {
                    cell.dataset.state = slot.state;
                    if (slot.state === 'mine') {
                        cell.classList.add('slot-mine');
                        cell.dataset.action = 'slot-details';
                        cell.dataset.bookingId = slot.booking_id;
                    } else if (slot.state === 'taken') {
                        cell.classList.add('slot-taken');
                        cell.dataset.action = 'slot-details';
                    } else if (slot.state === 'busy') {
                        cell.classList.add('slot-busy');
                        cell.textContent = 'Abwesend';
                    } else {
                        cell.classList.add('slot-free');
                        cell.dataset.action = 'book-slot';
                    }

                    if (slot.plan_text) {
                        const plan = document.createElement('div');
                        plan.className = 'small text-muted';
                        plan.textContent = slot.plan_text;
                        cell.appendChild(plan);
                    }
                    if (slot.room_name) {
                        const room = document.createElement('div');
                        room.className = 'badge bg-info text-dark mt-1';
                        room.textContent = `Raum ${slot.room_name}`;
                        cell.appendChild(room);
                    }
                    if (slot.is_trip) {
                        const tripBadge = document.createElement('div');
                        tripBadge.className = 'badge bg-danger mt-1';
                        tripBadge.textContent = 'Klassenfahrt';
                        cell.appendChild(tripBadge);
                    }
                } else {
                    cell.classList.add('slot-free');
                    cell.dataset.action = 'book-slot';
                }

                row.appendChild(cell);
            });

            classTableBody.appendChild(row);
        }
    };

    const openSlotDetails = async (classId, className, day, period) => {
        const data = await fetchJson('/api/slot_details.php', {
            csrf: csrfToken,
            class_id: classId,
            day,
            period,
        });
        if (!data.ok) {
            alert(data.error || 'Fehler beim Laden.');
            return;
        }

        activeSlot = { classId, className, day, period, myBookingId: data.my_booking_id };
        slotTitle.textContent = `${className} · Tag ${day} · Stunde ${period}`;

        slotPlanText.value = data.plan_text || '';
        slotPlanText.disabled = !data.can_edit;

        slotRoomSelect.innerHTML = '<option value="">-</option>';
        data.rooms.forEach((room) => {
            const option = document.createElement('option');
            option.value = room.id;
            option.textContent = room.name;
            if (Number(room.id) === Number(data.room_id)) {
                option.selected = true;
            }
            slotRoomSelect.appendChild(option);
        });
        slotRoomSelect.disabled = !data.can_edit;

        slotParticipants.innerHTML = '';
        if (data.participants.length) {
            data.participants.forEach((participant) => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.textContent = participant.initials;
                slotParticipants.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = 'Keine Buchungen.';
            slotParticipants.appendChild(li);
        }

        if (data.slot_type_summary) {
            slotTypeSummary.textContent = data.slot_type_summary === 'trip' ? 'Klassenfahrt-Slot' : 'Normale Buchung';
            slotTypeSummary.classList.remove('d-none');
        } else {
            slotTypeSummary.classList.add('d-none');
        }

        slotSave.disabled = !data.can_edit;
        slotDelete.disabled = !data.is_booked_by_me;
        slotJoin.classList.toggle('d-none', data.is_booked_by_me || !data.slot_has_any_booking);

        modal?.show();
    };

    const refreshAll = async () => {
        await buildMyTimetable();
        if (classSelect.value) {
            await buildClassMatrix(classSelect.value);
        }
    };

    document.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-action]');
        if (!target) {
            return;
        }
        const action = target.dataset.action;
        if (action === 'slot-details') {
            event.preventDefault();
            await openSlotDetails(
                Number(target.dataset.classId),
                target.dataset.className || '',
                Number(target.dataset.day),
                Number(target.dataset.period)
            );
        }
        if (action === 'book-slot') {
            event.preventDefault();
            const classId = Number(target.dataset.classId);
            const day = Number(target.dataset.day);
            const period = Number(target.dataset.period);
            const response = await fetchJson('/api/book.php', {
                csrf: csrfToken,
                class_id: classId,
                day,
                period,
            });
            if (!response.ok) {
                alert(response.error || 'Buchung fehlgeschlagen.');
                return;
            }
            await refreshAll();
        }
    });

    classSelect?.addEventListener('change', async (event) => {
        const value = event.target.value;
        await buildClassMatrix(value);
    });

    slotSave?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        const response = await fetchJson('/api/slot_update.php', {
            csrf: csrfToken,
            class_id: activeSlot.classId,
            day: activeSlot.day,
            period: activeSlot.period,
            plan_text: slotPlanText.value,
            room_id: slotRoomSelect.value,
        });
        if (!response.ok) {
            alert(response.error || 'Speichern fehlgeschlagen.');
            return;
        }
        await refreshAll();
        modal?.hide();
    });

    slotDelete?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        if (!confirm('Buchung wirklich löschen?')) {
            return;
        }
        const response = await fetchJson('/api/unbook.php', {
            csrf: csrfToken,
            booking_id: activeSlot.myBookingId,
            class_id: activeSlot.classId,
            day: activeSlot.day,
            period: activeSlot.period,
        });
        if (!response.ok) {
            alert(response.error || 'Buchung konnte nicht gelöscht werden.');
            return;
        }
        await refreshAll();
        modal?.hide();
    });

    slotJoin?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        const response = await fetchJson('/api/book.php', {
            csrf: csrfToken,
            class_id: activeSlot.classId,
            day: activeSlot.day,
            period: activeSlot.period,
        });
        if (!response.ok) {
            alert(response.error || 'Beitreten fehlgeschlagen.');
            return;
        }
        await refreshAll();
        modal?.hide();
    });

    slotAbsenceSlot?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        const response = await fetchJson('/api/absence_set.php', {
            csrf: csrfToken,
            day: activeSlot.day,
            period: activeSlot.period,
            all_day: false,
        });
        if (!response.ok) {
            if (response.needs_confirm && confirm('Buchungen gefunden. Trotzdem abwesend setzen?')) {
                const confirmResponse = await fetchJson('/api/absence_set.php', {
                    csrf: csrfToken,
                    day: activeSlot.day,
                    period: activeSlot.period,
                    all_day: false,
                    confirm: true,
                });
                if (!confirmResponse.ok) {
                    alert(confirmResponse.error || 'Abwesenheit fehlgeschlagen.');
                    return;
                }
            } else {
                alert(response.error || 'Abwesenheit fehlgeschlagen.');
                return;
            }
        }
        await refreshAll();
        modal?.hide();
    });

    slotAbsenceDay?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        const response = await fetchJson('/api/absence_set.php', {
            csrf: csrfToken,
            day: activeSlot.day,
            all_day: true,
        });
        if (!response.ok) {
            if (response.needs_confirm && confirm('Buchungen gefunden. Trotzdem abwesend setzen?')) {
                const confirmResponse = await fetchJson('/api/absence_set.php', {
                    csrf: csrfToken,
                    day: activeSlot.day,
                    all_day: true,
                    confirm: true,
                });
                if (!confirmResponse.ok) {
                    alert(confirmResponse.error || 'Abwesenheit fehlgeschlagen.');
                    return;
                }
            } else {
                alert(response.error || 'Abwesenheit fehlgeschlagen.');
                return;
            }
        }
        await refreshAll();
        modal?.hide();
    });

    slotTrip?.addEventListener('click', async () => {
        if (!activeSlot) {
            return;
        }
        if (!confirm('Klassenfahrt für diesen Slot setzen?')) {
            return;
        }
        const response = await fetchJson('/api/trip_set.php', {
            csrf: csrfToken,
            class_id: activeSlot.classId,
            day: activeSlot.day,
            period: activeSlot.period,
        });
        if (!response.ok) {
            alert(response.error || 'Klassenfahrt fehlgeschlagen.');
            return;
        }
        await refreshAll();
        modal?.hide();
    });

    buildMyTimetable();
});
