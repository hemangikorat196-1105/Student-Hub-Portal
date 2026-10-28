// Student Hub Portal - JSON Data
// Fetch -> Search -> Filter -> Sort -> Pagination -> Render

const DATASETS = {
    events: {
        file: "events.json",
        label: "Events"
    },

    students: {
        file: "students.json",
        label: "Students"
    },

    faqs: {
        file: "faqs.json",
        label: "FAQs"
    }
};


// --------------------------------------------------
// APPLICATION STATE
// --------------------------------------------------

const state = {
    type: "events",
    records: [],
    page: 1,
    perPage: 6,
    search: "",
    filter: "",
    sort: "default"
};


// --------------------------------------------------
// SHORTCUT FOR getElementById
// --------------------------------------------------

const $ = (id) => document.getElementById(id);


// --------------------------------------------------
// FETCH JSON DATA
// --------------------------------------------------

async function fetchJSON(file) {

    const response = await fetch(file, {
        cache: "no-store"
    });

    if (!response.ok) {
        throw new Error(
            `HTTP ${response.status}: ${response.statusText}`
        );
    }

    const data = await response.json();

    if (!Array.isArray(data)) {
        throw new Error(
            "JSON file must contain an array of records."
        );
    }

    return data;
}


// --------------------------------------------------
// ESCAPE HTML
// Prevents HTML injection when displaying JSON data
// --------------------------------------------------

function escapeHTML(value) {

    return String(value ?? "").replace(/[&<>'"]/g, function (character) {

        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "'": "&#39;",
            '"': "&quot;"
        }[character];

    });
}


// --------------------------------------------------
// GET FILTER FIELD
// --------------------------------------------------

function getFilterField() {

    if (state.type === "events") {
        return "category";
    }

    if (state.type === "students") {
        return "course";
    }

    if (state.type === "faqs") {
        return "category";
    }

    return "";
}


// --------------------------------------------------
// GET FILTERED RECORDS
//
// IMPORTANT:
// Default order = SAME ORDER AS JSON FILE
//
// Search = keeps JSON order
// Filter = keeps JSON order
// Sorting = only happens when selected
// --------------------------------------------------

function getFilteredRecords() {

    const searchTerm = state.search
        .trim()
        .toLowerCase();


    // --------------------------------------------------
    // STEP 1
    // Start with ORIGINAL JSON order
    // --------------------------------------------------

    let records = [...state.records];


    // --------------------------------------------------
    // STEP 2
    // SEARCH
    // --------------------------------------------------

    if (searchTerm) {

        records = records.filter(function (record) {

            return Object.values(record).some(function (value) {

                return String(value)
                    .toLowerCase()
                    .includes(searchTerm);

            });

        });

    }


    // --------------------------------------------------
    // STEP 3
    // FILTER
    // --------------------------------------------------

    if (state.filter) {

        const filterField = getFilterField();

        records = records.filter(function (record) {

            return String(record[filterField]) === state.filter;

        });

    }


    // --------------------------------------------------
    // STEP 4
    // DEFAULT ORDER
    //
    // DO NOT SORT.
    // This keeps the exact JSON order.
    // --------------------------------------------------

    if (state.sort === "default") {

        return records;

    }


    // --------------------------------------------------
    // STEP 5
    // SORTING
    // --------------------------------------------------

    const sortedRecords = [...records];

    const parts = state.sort.split("-");

    const field = parts[0];

    const direction = parts[1];


    sortedRecords.sort(function (a, b) {

        let valueA = a[field];
        let valueB = b[field];


        // ----------------------------------------------
        // DATE SORTING
        // ----------------------------------------------

        if (field === "date") {

            valueA = new Date(valueA);
            valueB = new Date(valueB);

        }


        // ----------------------------------------------
        // NUMBER SORTING
        // ----------------------------------------------

        if (
            field === "id" ||
            field === "cgpa"
        ) {

            valueA = Number(valueA);
            valueB = Number(valueB);

        }


        // ----------------------------------------------
        // STRING SORTING
        // ----------------------------------------------

        if (typeof valueA === "string") {

            const result = valueA.localeCompare(
                String(valueB)
            );

            return direction === "desc"
                ? -result
                : result;

        }


        // ----------------------------------------------
        // NUMBER / DATE SORTING
        // ----------------------------------------------

        const result = valueA - valueB;

        return direction === "desc"
            ? -result
            : result;

    });


    return sortedRecords;
}


// --------------------------------------------------
// RENDER SEARCH / FILTER / SORT CONTROLS
// --------------------------------------------------

function renderControls() {

    const filter = $("jsonFilter");
    const sort = $("jsonSort");
    const search = $("jsonSearch");


    if (!filter || !sort || !search) {
        return;
    }


    // --------------------------------------------------
    // SEARCH PLACEHOLDER
    // --------------------------------------------------

    search.placeholder =
        `Search ${DATASETS[state.type].label.toLowerCase()}...`;


    // --------------------------------------------------
    // FILTER OPTIONS
    // --------------------------------------------------

    const filterField = getFilterField();


    const values = [
        ...new Set(
            state.records
                .map(record => record[filterField])
                .filter(Boolean)
        )
    ].sort();


    let filterLabel;


    if (state.type === "students") {

        filterLabel = "Courses";

    } else if (state.type === "events") {

        filterLabel = "Categories";

    } else {

        filterLabel = "Categories";

    }


    filter.innerHTML =
        `<option value="">All ${filterLabel}</option>` +
        values
            .map(value =>
                `<option value="${escapeHTML(value)}">
                    ${escapeHTML(value)}
                </option>`
            )
            .join("");


    // --------------------------------------------------
    // SORT OPTIONS
    // --------------------------------------------------

    if (state.type === "events") {

        sort.innerHTML = `
            <option value="default">
                Default Order
            </option>

            <option value="date-asc">
                Date: Oldest First
            </option>

            <option value="date-desc">
                Date: Latest First
            </option>

            <option value="title-asc">
                Title: A-Z
            </option>

            <option value="title-desc">
                Title: Z-A
            </option>
        `;

    }


    else if (state.type === "students") {

        sort.innerHTML = `
            <option value="default">
                ID Order (JSON)
            </option>

            <option value="id-asc">
                ID: Low-High
            </option>

            <option value="id-desc">
                ID: High-Low
            </option>

            <option value="name-asc">
                Name: A-Z
            </option>

            <option value="name-desc">
                Name: Z-A
            </option>

            <option value="cgpa-desc">
                CGPA: High-Low
            </option>

            <option value="cgpa-asc">
                CGPA: Low-High
            </option>
        `;

    }


    else {

        sort.innerHTML = `
            <option value="default">
                Default Order
            </option>

            <option value="question-asc">
                Question: A-Z
            </option>

            <option value="question-desc">
                Question: Z-A
            </option>
        `;

    }


    // Make sure current selection is shown

    filter.value = state.filter;
    sort.value = state.sort;

}


// --------------------------------------------------
// RENDER RECORDS
// --------------------------------------------------

function renderRecords() {

    const allRecords = getFilteredRecords();


    // --------------------------------------------------
    // CALCULATE TOTAL PAGES
    // --------------------------------------------------

    const totalPages = Math.max(
        1,
        Math.ceil(
            allRecords.length / state.perPage
        )
    );


    // Prevent invalid page number

    if (state.page > totalPages) {

        state.page = totalPages;

    }


    // --------------------------------------------------
    // GET CURRENT PAGE
    // --------------------------------------------------

    const start =
        (state.page - 1) * state.perPage;


    const pageRecords =
        allRecords.slice(
            start,
            start + state.perPage
        );


    const output = $("jsonOutput");


    // --------------------------------------------------
    // COUNT
    // --------------------------------------------------

    $("jsonCount").textContent =
        `Showing ${pageRecords.length} of ${allRecords.length} matching records (${state.records.length} total)`;


    // --------------------------------------------------
    // PAGE INFORMATION
    // --------------------------------------------------

    $("jsonPageInfo").textContent =
        `Page ${state.page} of ${totalPages}`;


    // --------------------------------------------------
    // NO RECORDS
    // --------------------------------------------------

    if (!pageRecords.length) {

        output.innerHTML = `
            <div class="json-empty">
                No matching records found.
                Try another search or filter.
            </div>
        `;

    }


    // --------------------------------------------------
    // EVENTS
    // --------------------------------------------------

    else if (state.type === "events") {

        output.innerHTML = `
            <div class="json-grid">

                ${pageRecords.map(event => `

                    <article class="json-card">

                        <span class="json-tag">
                            ${escapeHTML(event.category)}
                        </span>

                        <h3>
                            ${escapeHTML(event.title)}
                        </h3>

                        <p>
                            ${escapeHTML(event.description)}
                        </p>

                        <strong>
                            📅 ${escapeHTML(event.date)}
                        </strong>

                        <br>

                        <span>
                            🕐 ${escapeHTML(event.time)}
                        </span>

                        <br>

                        <span>
                            📍 ${escapeHTML(event.location)}
                        </span>

                        <br>

                        <span>
                            🎤 ${escapeHTML(event.speaker)}
                        </span>

                        <br>

                        <span>
                            💺 Seats: ${escapeHTML(event.seats)}
                        </span>

                        <br>

                        <span>
                            📌 ${escapeHTML(event.status)}
                        </span>

                    </article>

                `).join("")}

            </div>
        `;

    }


    // --------------------------------------------------
    // STUDENTS
    // --------------------------------------------------

    else if (state.type === "students") {

        output.innerHTML = `

            <div class="json-table-wrap">

                <table class="json-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Student Name</th>

                            <th>Student ID</th>

                            <th>Course</th>

                            <th>Year</th>

                            <th>Department</th>

                            <th>Location</th>

                            <th>CGPA</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        ${pageRecords.map(student => `

                            <tr>

                                <td>
                                    ${escapeHTML(student.id)}
                                </td>


                                <td>

                                    <span class="student-name">
                                        ${escapeHTML(student.name)}
                                    </span>

                                    <small class="student-email">
                                        ${escapeHTML(student.email)}
                                    </small>

                                </td>


                                <td>
                                    ${escapeHTML(student.studentId)}
                                </td>


                                <td>
                                    ${escapeHTML(student.course)}
                                </td>


                                <td>
                                    ${escapeHTML(student.year)}
                                </td>


                                <td>
                                    ${escapeHTML(student.department)}
                                </td>


                                <td>

                                    ${escapeHTML(student.city)},
                                    ${escapeHTML(student.state)}

                                    <br>

                                    <small>
                                        ${escapeHTML(student.country)}
                                    </small>

                                </td>


                                <td>
                                    ${escapeHTML(student.cgpa)}
                                </td>


                                <td>

                                    <span class="student-status">
                                        ${escapeHTML(student.status)}
                                    </span>

                                </td>

                            </tr>

                        `).join("")}

                    </tbody>

                </table>

            </div>

        `;

    }


    // --------------------------------------------------
    // FAQ
    // --------------------------------------------------

    else {

        output.innerHTML = pageRecords.map(faq => `

            <div class="json-faq">

                <button type="button">

                    ${escapeHTML(faq.question)}

                    <span>+</span>

                </button>

                <p>
                    ${escapeHTML(faq.answer)}
                </p>

            </div>

        `).join("");


        // FAQ accordion

        output
            .querySelectorAll(".json-faq button")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function () {

                        this.parentElement.classList.toggle(
                            "open"
                        );

                    }
                );

            });

    }


    // --------------------------------------------------
    // PAGINATION
    // --------------------------------------------------

    renderPagination(totalPages);

}


// --------------------------------------------------
// PAGINATION
// --------------------------------------------------

function renderPagination(totalPages) {

    const box = $("jsonPagination");


    if (!box) {
        return;
    }


    box.innerHTML = "";


    // --------------------------------------------------
    // PREVIOUS
    // --------------------------------------------------

    const previous =
        document.createElement("button");


    previous.textContent = "Previous";


    previous.disabled =
        state.page === 1;


    previous.onclick = function () {

        if (state.page > 1) {

            state.page--;

            renderRecords();

        }

    };


    box.appendChild(previous);


    // --------------------------------------------------
    // PAGE NUMBERS
    // --------------------------------------------------

    for (
        let pageNumber = 1;
        pageNumber <= totalPages;
        pageNumber++
    ) {

        const button =
            document.createElement("button");


        button.textContent =
            pageNumber;


        button.className =
            pageNumber === state.page
                ? "active"
                : "";


        button.onclick = function () {

            state.page =
                pageNumber;

            renderRecords();

        };


        box.appendChild(button);

    }


    // --------------------------------------------------
    // NEXT
    // --------------------------------------------------

    const next =
        document.createElement("button");


    next.textContent =
        "Next";


    next.disabled =
        state.page === totalPages;


    next.onclick = function () {

        if (state.page < totalPages) {

            state.page++;

            renderRecords();

        }

    };


    box.appendChild(next);

}


// --------------------------------------------------
// LOAD DATASET
// --------------------------------------------------

async function loadDataset(type) {

    state.type = type;

    state.records = [];

    state.page = 1;

    state.search = "";

    state.filter = "";

    state.sort = "default";


    // Clear search

    if ($("jsonSearch")) {

        $("jsonSearch").value = "";

    }


    // Show loading

    if ($("jsonLoading")) {

        $("jsonLoading").hidden = false;

    }


    // Hide error

    if ($("jsonError")) {

        $("jsonError").hidden = true;

    }


    // Clear output

    if ($("jsonOutput")) {

        $("jsonOutput").innerHTML = "";

    }


    // Clear pagination

    if ($("jsonPagination")) {

        $("jsonPagination").innerHTML = "";

    }


    try {

        // Fetch JSON

        state.records =
            await fetchJSON(
                DATASETS[type].file
            );


        // Hide loading

        if ($("jsonLoading")) {

            $("jsonLoading").hidden = true;

        }


        // Success message

        if ($("jsonSource")) {

            $("jsonSource").textContent =
                `${DATASETS[type].file} • Fetch API success`;

        }


        // Render controls

        renderControls();


        // Render records

        renderRecords();

    }


    catch (error) {

        console.error(
            "JSON loading error:",
            error
        );


        // Hide loading

        if ($("jsonLoading")) {

            $("jsonLoading").hidden = true;

        }


        // Show error

        if ($("jsonError")) {

            $("jsonError").hidden = false;

        }


        if ($("jsonErrorMessage")) {

            $("jsonErrorMessage").textContent =
                `Unable to load ${DATASETS[type].file}: ${error.message}`;

        }


        if ($("jsonSource")) {

            $("jsonSource").textContent =
                "Fetch API error";

        }

    }

}


// --------------------------------------------------
// BIND ALL EVENTS
// --------------------------------------------------

function bindEvents() {


    // --------------------------------------------------
    // DATASET TABS
    // --------------------------------------------------

    document
        .querySelectorAll(".json-tab")
        .forEach(tab => {

            tab.addEventListener(
                "click",
                function () {

                    document
                        .querySelectorAll(".json-tab")
                        .forEach(t =>
                            t.classList.remove("active")
                        );


                    this.classList.add("active");


                    loadDataset(
                        this.dataset.type
                    );

                }
            );

        });


    // --------------------------------------------------
    // SEARCH
    // --------------------------------------------------

    $("jsonSearch")
        .addEventListener(
            "input",
            function (event) {

                state.search =
                    event.target.value;

                state.page = 1;

                renderRecords();

            }
        );


    // --------------------------------------------------
    // FILTER
    // --------------------------------------------------

    $("jsonFilter")
        .addEventListener(
            "change",
            function (event) {

                state.filter =
                    event.target.value;

                state.page = 1;

                renderRecords();

            }
        );


    // --------------------------------------------------
    // SORT
    // --------------------------------------------------

    $("jsonSort")
        .addEventListener(
            "change",
            function (event) {

                state.sort =
                    event.target.value;

                state.page = 1;

                renderRecords();

            }
        );


    // --------------------------------------------------
    // RESET
    // --------------------------------------------------

    $("jsonReset")
        .addEventListener(
            "click",
            function () {

                state.search = "";

                state.filter = "";

                state.sort = "default";

                state.page = 1;


                $("jsonSearch").value = "";

                $("jsonFilter").value = "";

                $("jsonSort").value = "default";


                renderRecords();

            }
        );


    // --------------------------------------------------
    // RETRY
    // --------------------------------------------------

    $("jsonRetry")
        .addEventListener(
            "click",
            function () {

                loadDataset(
                    state.type
                );

            }
        );

}


// --------------------------------------------------
// PAGE START
// --------------------------------------------------

document.addEventListener(
    "DOMContentLoaded",
    function () {

        bindEvents();

        loadDataset("events");

    }
);