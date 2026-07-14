$(document).ready(function () {
  let optCabang = "";

  widgetTotalPasien({ periode: "bulanan" });
  widgetRawatInap({ periode: "bulanan" });
  widgetDaftarBarangLimit();
  widgetTidakPengabaran({ periode: "bulanan" });
  loadCabang();
  let getAuthUser = localStorage.getItem("vet-clinic");

  if (!getAuthUser) {
    location.href = $(".baseUrl").val() + "/masuk";
  } else {
    getAuthUser = JSON.parse(getAuthUser);
    role = getAuthUser.role.toLowerCase();

    if (role !== "admin" && role !== "dokter" && role !== "resepsionis" && role !== "paramedis") {
      $(".pasien").hide();
      $(".rawat-inap").hide();
      $(".tidak-pengabaran").hide();
      $(".daftar-barang-limit-expired").hide();
    }
  }

  let currentPeriodePasien = "bulanan";
  let pasienDateFrom = null;
  let pasienDateTo = null;

  initPasienDatepicker(currentPeriodePasien);

  $("#periode-pasien-group .btn").on("click", function () {
    $("#periode-pasien-group .btn").removeClass("active");
    $(this).addClass("active");

    const prev = currentPeriodePasien;
    currentPeriodePasien = $(this).data("periode");

    const titles = {
      harian: "Jumlah pasien per cabang per hari",
      mingguan: "Jumlah pasien per cabang per minggu",
      bulanan: "Jumlah pasien per cabang per bulan",
    };
    $("#jumlah-pasien-title").text(titles[currentPeriodePasien]);

    if (prev === "mingguan") {
      $("#datepicker-pasien-range").data("daterangepicker") &&
        $("#datepicker-pasien-range").data("daterangepicker").remove();
      $("#datepicker-pasien-range").val("");
      pasienDateFrom = null;
      pasienDateTo = null;
    } else {
      $("#datepicker-jumlah-pasien").datepicker("destroy").val("");
    }

    initPasienDatepicker(currentPeriodePasien);
    widgetTotalPasien({ periode: currentPeriodePasien });
  });

  function initPasienDatepicker(periode) {
    if (periode === "mingguan") {
      $("#datepicker-single-wrapper").hide();
      $("#datepicker-range-wrapper").show();
      initRangeDatepicker();
    } else {
      $("#datepicker-range-wrapper").hide();
      $("#datepicker-single-wrapper").show();

      const placeholders = { harian: "yyyy-mm-dd", bulanan: "mm-yyyy" };
      const options = { autoclose: true, clearBtn: true, todayHighlight: true };

      if (periode === "bulanan") {
        options.format = "mm-yyyy";
        options.startView = "months";
        options.minViewMode = "months";
      } else {
        options.format = "yyyy-mm-dd";
      }

      $("#datepicker-jumlah-pasien")
        .attr("placeholder", placeholders[periode])
        .datepicker(options)
        .off("changeDate")
        .off("clearDate")
        .on("changeDate", function (e) {
          const val = e.format();
          if (periode === "bulanan") {
            const parts = val.split("-");
            widgetTotalPasien({ month: parts[0], year: parts[1], periode });
          } else {
            widgetTotalPasien({ date: val, periode });
          }
        })
        .on("clearDate", function () {
          widgetTotalPasien({ periode });
        });
    }
  }

  function initRangeDatepicker() {
    pasienDateFrom = null;
    pasienDateTo = null;

    const $input = $("#datepicker-pasien-range");
    $input.val("");

    // Destroy previous instance if it exists
    if ($input.data("daterangepicker")) {
      $input.data("daterangepicker").remove();
    }

    $input.off("apply.daterangepicker cancel.daterangepicker");

    $input.daterangepicker({
      autoUpdateInput: false,
      autoApply: false,
      linkedCalendars: true,
      opens: "right",
      locale: {
        format: "YYYY-MM-DD",
        applyLabel: "Terapkan",
        cancelLabel: "Batal",
        fromLabel: "Dari",
        toLabel: "Sampai",
        daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
        monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
          "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
        firstDay: 1,
      },
    });

    $input.on("apply.daterangepicker", function (ev, picker) {
      pasienDateFrom = picker.startDate.format("YYYY-MM-DD");
      pasienDateTo = picker.endDate.format("YYYY-MM-DD");
      $input.val(pasienDateFrom + " - " + pasienDateTo);
      widgetTotalPasien({ date_from: pasienDateFrom, date_to: pasienDateTo, periode: "mingguan" });
    });

    $input.on("cancel.daterangepicker", function () {
      $input.val("");
      pasienDateFrom = null;
      pasienDateTo = null;
      widgetTotalPasien({ periode: "mingguan" });
    });

    // Make the calendar icon also open the picker
    $("#pasien-range-group .input-group-addon").off("click.drp").on("click.drp", function () {
      $input.trigger("click");
    });
  }

  let currentPeriodeRawatInap = "bulanan";
  let rawatInapDateFrom = null;
  let rawatInapDateTo = null;

  initRawatInapDatepicker(currentPeriodeRawatInap);

  $("#periode-rawat-inap-group .btn").on("click", function () {
    $("#periode-rawat-inap-group .btn").removeClass("active");
    $(this).addClass("active");

    const prev = currentPeriodeRawatInap;
    currentPeriodeRawatInap = $(this).data("periode");

    const titles = {
      harian: "Rawat Inap per Hari",
      mingguan: "Rawat Inap per Minggu",
      bulanan: "Rawat Inap per Bulan",
    };
    $("#rawat-inap-title").text(titles[currentPeriodeRawatInap]);

    if (prev === "mingguan") {
      $("#datepicker-rawat-inap-range").data("daterangepicker") &&
        $("#datepicker-rawat-inap-range").data("daterangepicker").remove();
      $("#datepicker-rawat-inap-range").val("");
      rawatInapDateFrom = null;
      rawatInapDateTo = null;
    } else {
      $("#datepicker-rawat-inap").datepicker("destroy").val("");
    }

    initRawatInapDatepicker(currentPeriodeRawatInap);
    widgetRawatInap({ periode: currentPeriodeRawatInap });
  });

  function initRawatInapDatepicker(periode) {
    if (periode === "mingguan") {
      $("#datepicker-single-rawat-inap-wrapper").hide();
      $("#datepicker-range-rawat-inap-wrapper").show();
      initRawatInapRangeDatepicker();
    } else {
      $("#datepicker-range-rawat-inap-wrapper").hide();
      $("#datepicker-single-rawat-inap-wrapper").show();

      const placeholders = { harian: "yyyy-mm-dd", bulanan: "mm-yyyy" };
      const options = { autoclose: true, clearBtn: true, todayHighlight: true };

      if (periode === "bulanan") {
        options.format = "mm-yyyy";
        options.startView = "months";
        options.minViewMode = "months";
      } else {
        options.format = "yyyy-mm-dd";
      }

      $("#datepicker-rawat-inap")
        .attr("placeholder", placeholders[periode])
        .datepicker(options)
        .off("changeDate")
        .off("clearDate")
        .on("changeDate", function (e) {
          const val = e.format();
          if (periode === "bulanan") {
            const parts = val.split("-");
            widgetRawatInap({ month: parts[0], year: parts[1], periode });
          } else {
            widgetRawatInap({ date: val, periode });
          }
        })
        .on("clearDate", function () {
          widgetRawatInap({ periode });
        });
    }
  }

  function initRawatInapRangeDatepicker() {
    rawatInapDateFrom = null;
    rawatInapDateTo = null;

    const $input = $("#datepicker-rawat-inap-range");
    $input.val("");

    if ($input.data("daterangepicker")) {
      $input.data("daterangepicker").remove();
    }

    $input.off("apply.daterangepicker cancel.daterangepicker");

    $input.daterangepicker({
      autoUpdateInput: false,
      autoApply: false,
      linkedCalendars: true,
      opens: "right",
      locale: {
        format: "YYYY-MM-DD",
        applyLabel: "Terapkan",
        cancelLabel: "Batal",
        fromLabel: "Dari",
        toLabel: "Sampai",
        daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
        monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
          "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
        firstDay: 1,
      },
    });

    $input.on("apply.daterangepicker", function (ev, picker) {
      rawatInapDateFrom = picker.startDate.format("YYYY-MM-DD");
      rawatInapDateTo = picker.endDate.format("YYYY-MM-DD");
      $input.val(rawatInapDateFrom + " - " + rawatInapDateTo);
      widgetRawatInap({ date_from: rawatInapDateFrom, date_to: rawatInapDateTo, periode: "mingguan" });
    });

    $input.on("cancel.daterangepicker", function () {
      $input.val("");
      rawatInapDateFrom = null;
      rawatInapDateTo = null;
      widgetRawatInap({ periode: "mingguan" });
    });

    $("#rawat-inap-range-group .input-group-addon").off("click.drp").on("click.drp", function () {
      $input.trigger("click");
    });
  }

  $("#filterCabangRawatInap").select2({
    placeholder: "Cabang",
    allowClear: true,
  });

  function widgetTotalPasien(param) {
    const requestData = { periode: param.periode || "bulanan" };
    if (param.month) requestData.month = param.month;
    if (param.year) requestData.year = param.year;
    if (param.date) requestData.date = param.date;
    if (param.date_from) requestData.date_from = param.date_from;
    if (param.date_to) requestData.date_to = param.date_to;

    $.ajax({
      url: $(".baseUrl").val() + "/api/dashboard/barchart",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: requestData,
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const getData = resp;

        const branchSet = [...new Set(getData.map((d) => d.branch_name))];
        const complaintSet = [...new Set(getData.map((d) => d.complaint_name))];

        const series = complaintSet.map((complaint) => ({
          name: complaint,
          data: branchSet.map((branch) => {
            const found = getData.find(
              (d) => d.branch_name === branch && d.complaint_name === complaint
            );
            return found ? found.total_patient : 0;
          }),
        }));

        Highcharts.chart("totalPasienWidget", {
          chart: { type: "column" },
          title: { text: "" },
          xAxis: { categories: branchSet },
          legend: { enabled: true },
          credits: { enabled: false },
          plotOptions: {
            column: {
              stacking: "normal",
              dataLabels: { enabled: false },
              cursor: "pointer",
              point: {
                events: {
                  click: function () {
                    const branchName    = this.category;
                    const complaintName = this.series.name;
                    const colIdx        = complaintSet.indexOf(complaintName) + 1; // +1 karena kolom 0 = Cabang
                    const rowIdx        = branchSet.indexOf(branchName);

                    // Hapus highlight sebelumnya
                    $("#tabel-pasien-cabang td.tabel-highlight").removeClass("tabel-highlight");

                    if (rowIdx === -1 || colIdx === 0) return;

                    // Highlight cell yang sesuai
                    const $row = $("#tabel-pasien-cabang-body tr").eq(rowIdx);
                    $row.find("td").eq(colIdx).addClass("tabel-highlight");

                    // Scroll ke tabel
                    $("html, body").animate({
                      scrollTop: $("#tabel-pasien-cabang").offset().top - 20
                    }, 300);
                  },
                },
              },
            },
          },
          yAxis: {
            title: { text: "Jumlah Pasien" },
            stackLabels: {
              enabled: true,
              style: {
                fontWeight: "bold",
                color: "#333",
                textOutline: "none",
              },
            },
          },
          tooltip: {
            headerFormat: "<b>{point.x}</b><br/>",
            pointFormat: "{series.name}: <b>{point.y}</b><br/>Total: <b>{point.stackTotal}</b>",
          },
          series: series,
        });

        // Render tabel pivot di bawah chart
        const thead =
          "<tr><th>Cabang</th>" +
          complaintSet.map(function (c) { return "<th>" + c + "</th>"; }).join("") +
          "<th style='background:#f5f5f5;'>Total</th></tr>";

        let tbody = "";
        const grandTotals = complaintSet.map(function () { return 0; });
        let grandTotal = 0;

        branchSet.forEach(function (branch) {
          let rowTotal = 0;
          const cells = complaintSet.map(function (complaint, ci) {
            const found = getData.find(function (d) {
              return d.branch_name === branch && d.complaint_name === complaint;
            });
            const val = found ? found.total_patient : 0;
            rowTotal += val;
            grandTotals[ci] += val;
            return "<td>" + (val > 0 ? val : "-") + "</td>";
          }).join("");
          grandTotal += rowTotal;
          tbody +=
            "<tr><td>" + branch + "</td>" + cells +
            "<td style='background:#f5f5f5;font-weight:bold;'>" + rowTotal + "</td></tr>";
        });

        tbody +=
          "<tr style='background:#e8f4fd;font-weight:bold;'><td>Total</td>" +
          grandTotals.map(function (t) { return "<td>" + t + "</td>"; }).join("") +
          "<td style='background:#d5eaf8;'>" + grandTotal + "</td></tr>";

        $("#tabel-pasien-cabang-head").html(thead);
        $("#tabel-pasien-cabang-body").html(tbody);
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function widgetRawatInap(param) {
    param = param || {};
    const requestData = { periode: param.periode || "bulanan" };
    if (param.month) requestData.month = param.month;
    if (param.year) requestData.year = param.year;
    if (param.date) requestData.date = param.date;
    if (param.date_from) requestData.date_from = param.date_from;
    if (param.date_to) requestData.date_to = param.date_to;

    $.ajax({
      url: $(".baseUrl").val() + "/api/dashboard/barchart-inpatient",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: requestData,
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const getData = resp;
        const tempDataSeries = [];
        const categoriesXAxis = [];

        getData.forEach((dt) => {
          categoriesXAxis.push(dt.branch_name);
          tempDataSeries.push({ name: dt.branch_name, y: dt.total_patient });
        });

        const finalSeries = [
          { name: "Total Pasien Widget", data: tempDataSeries },
        ];

        Highcharts.chart("rawatInapWidget", {
          chart: { type: "column" },
          title: { text: "" },
          xAxis: { categories: categoriesXAxis },
          legend: { enabled: false },
          credits: { enabled: false },
          plotOptions: {
            column: {
              dataLabels: { enabled: true },
            },
          },
          yAxis: { title: { text: "" } },
          series: finalSeries,
        });
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function widgetDaftarBarangLimit() {
    let paramUrlSetup = {
      orderby: "",
      column: "",
      keyword: "",
      branchId: "",
    };

    if (role != "admin") {
      $(".section-right-box-title").append(`
        <div class="input-search-section m-r-10px">
          <input type="text" class="form-control" placeholder="cari.." id="btnFindClinic">
          <i class="fa fa-search" aria-hidden="true"></i>
        </div>
      `);

      $(".section-right-box-title-pet").append(`
        <div class="input-search-section m-r-10px">
          <input type="text" class="form-control" placeholder="cari.." id="btnFindPet">
          <i class="fa fa-search" aria-hidden="true"></i>
        </div>
      `);
    } else {
      
      $(".section-right-box-title").append(`
        <div class="input-search-section m-r-10px">
          <input type="text" class="form-control" placeholder="cari.." id="btnFindClinic">
          <i class="fa fa-search" aria-hidden="true"></i>
        </div>
        <select id="filterCabangDaftarBarangLimit" style="width: 50%"></select>
      `);

      $(".section-right-box-title-pet").append(`
        <div class="input-search-section m-r-10px">
          <input type="text" class="form-control" placeholder="cari.." id="btnFindPet">
          <i class="fa fa-search" aria-hidden="true"></i>
        </div>
        <select id="filterCabangDaftarBarangLimitPet" style="width: 50%"></select>
      `);
    }

    // $("#btnFindClinic").click(function () {
    //   paramUrlSetup.keyword = $("#btnFindClinic").val();
    //   loadDaftarBarangLimit(paramUrlSetup);
    // });

    // $("#btnFindPet").click(function () {
    //   paramUrlSetup.keyword = $("#btnFindPet").val();
    //   loadDaftarBarangLimitPet(paramUrlSetup);
    // });

    $("#btnFindClinic").keypress(function (e) {
      if (e.which == 13) {
        paramUrlSetup.keyword = $(this).val();
        loadDaftarBarangLimit(paramUrlSetup);
      }
    });

    $("#btnFindPet").keypress(function (e) {
      if (e.which == 13) {
        paramUrlSetup.keyword = $(this).val();
        loadDaftarBarangLimitPet(paramUrlSetup);
      }
    });

    $(".onOrdering").click(function () {
      const column = $(this).attr("data");
      const orderBy = $(this).attr("orderby");
      $('.onOrdering[data="' + column + '"]')
        .children()
        .remove();

      if (orderBy == "none" || orderBy == "asc") {
        $(this).attr("orderby", "desc");
        $(this).append('<span class="fa fa-sort-desc"></span>');
      } else if (orderBy == "desc") {
        $(this).attr("orderby", "asc");
        $(this).append('<span class="fa fa-sort-asc"></span>');
      }

      paramUrlSetup.orderby = $(this).attr("orderby");
      paramUrlSetup.column = column;

      loadDaftarBarangLimit(paramUrlSetup);
    });

    $(".onOrderingPet").click(function () {
      const column = $(this).attr("data");
      const orderBy = $(this).attr("orderby");
      $('.onOrderingPet[data="' + column + '"]')
        .children()
        .remove();

      if (orderBy == "none" || orderBy == "asc") {
        $(this).attr("orderby", "desc");
        $(this).append('<span class="fa fa-sort-desc"></span>');
      } else if (orderBy == "desc") {
        $(this).attr("orderby", "asc");
        $(this).append('<span class="fa fa-sort-asc"></span>');
      }

      paramUrlSetup.orderby = $(this).attr("orderby");
      paramUrlSetup.column = column;

      loadDaftarBarangLimitPet(paramUrlSetup);
    });

    $("#filterCabangDaftarBarangLimit").select2({
      placeholder: "Cabang",
      allowClear: true,
    });

    $("#filterCabangDaftarBarangLimitPet").select2({
      placeholder: "Cabang",
      allowClear: true,
    });

    $("#filterCabangDaftarBarangLimit").on("select2:select", function () {
      paramUrlSetup.branchId = $(this).val();
      loadDaftarBarangLimit(paramUrlSetup);
    });
    $("#filterCabangDaftarBarangLimitPet").on("select2:select", function () {
      paramUrlSetup.branchId = $(this).val();
      loadDaftarBarangLimitPet(paramUrlSetup);
    });

    $("#filterCabangDaftarBarangLimit").on("select2:unselect", function () {
      paramUrlSetup.branchId = $(this).val();
      loadDaftarBarangLimit(paramUrlSetup);
    });
    $("#filterCabangDaftarBarangLimitPet").on("select2:unselect", function () {
      paramUrlSetup.branchId = $(this).val();
      loadDaftarBarangLimitPet(paramUrlSetup);
    });

    loadDaftarBarangLimit(paramUrlSetup);
    loadDaftarBarangLimitPet(paramUrlSetup);
  }

  function loadDaftarBarangLimit(paramUrlSetupDaftar) {
    let getCurrentPage = 1;
    $.ajax({
      url: $(".baseUrl").val() + "/api/daftar-barang-batas",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        orderby: paramUrlSetupDaftar.orderby,
        column: paramUrlSetupDaftar.column,
        keyword: paramUrlSetupDaftar.keyword,
        branch_id: paramUrlSetupDaftar.branchId,
        page: getCurrentPage,
      },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const getData = resp.data;
        let listDaftarBarangLimit = "";
        $("#list-daftar-barang-limit tr").remove();

        if (getData.length) {
          $.each(getData, function (idx, v) {
            listDaftarBarangLimit +=
              `<tr>` +
              `<td class="${
                v.diff_expired_days < 60 ? "expired-date" : ""
              }">${++idx}</td>` +
              `<td class="${v.diff_item < 0 ? "item-outstock" : ""}">${
                v.item_name
              }</td>` +
              `<td>${v.total_item}</td>` +
              `<td>${v.unit_name}</td>` +
              `<td>${v.category_name}</td>` +
              `<td>${v.branch_name}</td>` +
              `<td>${v.created_by}</td>` +
              `<td>${v.created_at}</td>` +
              `<td>${v.expired_date}</td>` +
              `</tr>`;
          });
        } else {
          listDaftarBarangLimit += `<tr class="text-center"><td colspan="12">Tidak ada data.</td></tr>`;
        }
        $("#list-daftar-barang-limit").append(listDaftarBarangLimit);

        generatePagination(getCurrentPage, resp.total_paging);

        $(".pagination > li > a").click(function () {
          const getClassName = this.className;
          const getNumber = parseFloat($(this).text());

          if (
            (getCurrentPage === 1 && getClassName.includes("arrow-left")) ||
            (getCurrentPage === resp.total_paging &&
              getClassName.includes("arrow-right"))
          ) {
            return;
          }

          if (getClassName.includes("arrow-left")) {
            getCurrentPage = getCurrentPage - 1;
          } else if (getClassName.includes("arrow-right")) {
            getCurrentPage = getCurrentPage + 1;
          } else {
            getCurrentPage = getNumber;
          }

          loadHasilPemeriksaan();
        });
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadDaftarBarangLimitPet(paramUrlSetupDaftar) {
    let getCurrentPage = 1;
    $.ajax({
      url: $(".baseUrl").val() + "/api/daftar-barang-batas-pet-shop",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        orderby: paramUrlSetupDaftar.orderby,
        column: paramUrlSetupDaftar.column,
        keyword: paramUrlSetupDaftar.keyword,
        branch_id: paramUrlSetupDaftar.branchId,
        page: getCurrentPage,
      },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const getData = resp.data;
        let listDaftarBarangLimitPetShop = "";
        $("#list-daftar-barang-limit-pet-shop tr").remove();

        if (getData.length) {
          $.each(getData, function (idx, v) {
            listDaftarBarangLimitPetShop +=
              `<tr>` +
              `<td class="${
                v.diff_expired_days < 60 ? "expired-date" : ""
              }">${++idx}</td>` +
              `<td class="${v.diff_item < 0 ? "item-outstock" : ""}">${
                v.item_name
              }</td>` +
              `<td>${v.total_item}</td>` +
              `<td>${v.branch_name}</td>` +
              `<td>${v.created_by}</td>` +
              `<td>${v.created_at}</td>` +
              `<td>${v.expired_date}</td>` +
              `</tr>`;
          });
        } else {
          listDaftarBarangLimitPetShop += `<tr class="text-center"><td colspan="12">Tidak ada data.</td></tr>`;
        }
        $("#list-daftar-barang-limit-pet-shop").append(listDaftarBarangLimitPetShop);

        generatePagination(getCurrentPage, resp.total_paging);

        $(".pagination > li > a").click(function () {
          const getClassName = this.className;
          const getNumber = parseFloat($(this).text());

          if (
            (getCurrentPage === 1 && getClassName.includes("arrow-left")) ||
            (getCurrentPage === resp.total_paging &&
              getClassName.includes("arrow-right"))
          ) {
            return;
          }

          if (getClassName.includes("arrow-left")) {
            getCurrentPage = getCurrentPage - 1;
          } else if (getClassName.includes("arrow-right")) {
            getCurrentPage = getCurrentPage + 1;
          } else {
            getCurrentPage = getNumber;
          }

          loadHasilPemeriksaan();
        });
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  // ── Tidak Pengabaran ──────────────────────────────────────────────────────
  const TIDAK_PENGABARAN_PER_PAGE = 25;
  let tidakPengabaranAllData      = [];
  let tidakPengabaranCurrentPage  = 1;
  let currentPeriodeTidakPengabaran = "bulanan";
  let tidakPengabaranDateFrom = null;
  let tidakPengabaranDateTo   = null;
  let tidakPengabaranBranchId = "";

  $("#filterCabangTidakPengabaran").select2({
    placeholder: "Semua Cabang",
    allowClear: true,
  });

  $("#filterCabangTidakPengabaran").on("select2:select", function () {
    tidakPengabaranBranchId = $(this).val();
    widgetTidakPengabaran(buildTidakPengabaranParam());
  });

  $("#filterCabangTidakPengabaran").on("select2:unselect", function () {
    tidakPengabaranBranchId = "";
    widgetTidakPengabaran(buildTidakPengabaranParam());
  });

  function buildTidakPengabaranParam() {
    const param = { periode: currentPeriodeTidakPengabaran };
    if (tidakPengabaranBranchId) param.branch_id = tidakPengabaranBranchId;
    if (currentPeriodeTidakPengabaran === "mingguan") {
      if (tidakPengabaranDateFrom) param.date_from = tidakPengabaranDateFrom;
      if (tidakPengabaranDateTo)   param.date_to   = tidakPengabaranDateTo;
    }
    return param;
  }

  initTidakPengabaranDatepicker(currentPeriodeTidakPengabaran);

  $("#periode-tidak-pengabaran-group .btn").on("click", function () {
    $("#periode-tidak-pengabaran-group .btn").removeClass("active");
    $(this).addClass("active");

    const prev = currentPeriodeTidakPengabaran;
    currentPeriodeTidakPengabaran = $(this).data("periode");

    const titles = {
      harian:   "Pasien tidak pengabaran per hari",
      mingguan: "Pasien tidak pengabaran per minggu",
      bulanan:  "Pasien tidak pengabaran per bulan",
    };
    $("#tidak-pengabaran-title").text(titles[currentPeriodeTidakPengabaran]);

    if (prev === "mingguan") {
      const drp = $("#datepicker-tidak-pengabaran-range").data("daterangepicker");
      if (drp) drp.remove();
      $("#datepicker-tidak-pengabaran-range").val("");
      tidakPengabaranDateFrom = null;
      tidakPengabaranDateTo   = null;
    } else {
      $("#datepicker-tidak-pengabaran").datepicker("destroy").val("");
    }

    initTidakPengabaranDatepicker(currentPeriodeTidakPengabaran);
    widgetTidakPengabaran(buildTidakPengabaranParam());
  });

  function initTidakPengabaranDatepicker(periode) {
    if (periode === "mingguan") {
      $("#datepicker-single-tidak-pengabaran-wrapper").hide();
      $("#datepicker-range-tidak-pengabaran-wrapper").show();
      initTidakPengabaranRangeDatepicker();
    } else {
      $("#datepicker-range-tidak-pengabaran-wrapper").hide();
      $("#datepicker-single-tidak-pengabaran-wrapper").show();

      const options = { autoclose: true, clearBtn: true, todayHighlight: true };
      if (periode === "bulanan") {
        options.format      = "mm-yyyy";
        options.startView   = "months";
        options.minViewMode = "months";
        $("#datepicker-tidak-pengabaran").attr("placeholder", "mm-yyyy");
      } else {
        options.format = "yyyy-mm-dd";
        $("#datepicker-tidak-pengabaran").attr("placeholder", "yyyy-mm-dd");
      }

      $("#datepicker-tidak-pengabaran")
        .datepicker(options)
        .off("changeDate clearDate")
        .on("changeDate", function (e) {
          const val = e.format();
          if (periode === "bulanan") {
            const parts = val.split("-");
            widgetTidakPengabaran({ ...buildTidakPengabaranParam(), month: parts[0], year: parts[1], periode });
          } else {
            widgetTidakPengabaran({ ...buildTidakPengabaranParam(), date: val, periode });
          }
        })
        .on("clearDate", function () {
          widgetTidakPengabaran(buildTidakPengabaranParam());
        });
    }
  }

  function initTidakPengabaranRangeDatepicker() {
    tidakPengabaranDateFrom = null;
    tidakPengabaranDateTo   = null;

    const $input = $("#datepicker-tidak-pengabaran-range");
    $input.val("");

    if ($input.data("daterangepicker")) $input.data("daterangepicker").remove();
    $input.off("apply.daterangepicker cancel.daterangepicker");

    $input.daterangepicker({
      autoUpdateInput:  false,
      autoApply:        false,
      linkedCalendars:  true,
      opens:            "right",
      locale: {
        format:      "YYYY-MM-DD",
        applyLabel:  "Terapkan",
        cancelLabel: "Batal",
        fromLabel:   "Dari",
        toLabel:     "Sampai",
        daysOfWeek:  ["Min","Sen","Sel","Rab","Kam","Jum","Sab"],
        monthNames:  ["Januari","Februari","Maret","April","Mei","Juni",
                      "Juli","Agustus","September","Oktober","November","Desember"],
        firstDay: 1,
      },
    });

    $input.on("apply.daterangepicker", function (ev, picker) {
      tidakPengabaranDateFrom = picker.startDate.format("YYYY-MM-DD");
      tidakPengabaranDateTo   = picker.endDate.format("YYYY-MM-DD");
      $input.val(tidakPengabaranDateFrom + " - " + tidakPengabaranDateTo);
      widgetTidakPengabaran(buildTidakPengabaranParam());
    });

    $input.on("cancel.daterangepicker", function () {
      $input.val("");
      tidakPengabaranDateFrom = null;
      tidakPengabaranDateTo   = null;
      widgetTidakPengabaran(buildTidakPengabaranParam());
    });

    $("#tidak-pengabaran-range-group .input-group-addon")
      .off("click.drp")
      .on("click.drp", function () { $input.trigger("click"); });
  }

  function widgetTidakPengabaran(param) {
    param = param || {};
    const requestData = { periode: param.periode || "bulanan" };
    if (param.month)     requestData.month     = param.month;
    if (param.year)      requestData.year      = param.year;
    if (param.date)      requestData.date      = param.date;
    if (param.date_from) requestData.date_from = param.date_from;
    if (param.date_to)   requestData.date_to   = param.date_to;
    if (param.branch_id) requestData.branch_id = param.branch_id;

    $.ajax({
      url:     $(".baseUrl").val() + "/api/dashboard/tidak-pengabaran",
      headers: { Authorization: `Bearer ${token}` },
      type:    "GET",
      data:    requestData,
      beforeSend: function () { $("#loading-screen").show(); },
      success: function (resp) {
        const chartData = resp.chart;
        const listData  = resp.list;

        // render bar chart
        const categories = chartData.map((d) => d.branch_name);
        const seriesData = chartData.map((d) => ({
          name: d.branch_name,
          y:    d.total,
        }));

        Highcharts.chart("tidakPengabaranChart", {
          chart:   { type: "column" },
          title:   { text: "" },
          credits: { enabled: false },
          xAxis:   { categories: categories, title: { text: "Cabang" } },
          yAxis:   {
            title:        { text: "Jumlah Pasien" },
            allowDecimals: false,
          },
          legend:  { enabled: false },
          colors:  ["#f39c12"],
          plotOptions: {
            column: {
              dataLabels: { enabled: true, style: { textOutline: "none" } },
            },
          },
          series: [{ name: "Tidak Pengabaran", data: seriesData }],
        });

        // store all data and render page 1
        tidakPengabaranAllData     = listData;
        tidakPengabaranCurrentPage = 1;
        renderTidakPengabaranTable(1);
      },
      complete: function () { $("#loading-screen").hide(); },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        } else {
          $("#list-tidak-pengabaran").html(
            `<tr class="text-center"><td colspan="6" style="color:red;">Gagal memuat data (${err.status}).</td></tr>`
          );
        }
      },
    });
  }
  function renderTidakPengabaranTable(page) {
    tidakPengabaranCurrentPage  = page;
    const total     = tidakPengabaranAllData.length;
    const totalPage = Math.ceil(total / TIDAK_PENGABARAN_PER_PAGE);
    const start     = (page - 1) * TIDAK_PENGABARAN_PER_PAGE;
    const pageData  = tidakPengabaranAllData.slice(start, start + TIDAK_PENGABARAN_PER_PAGE);

    let rows = "";
    if (total === 0) {
      rows = `<tr class="text-center"><td colspan="6">Tidak ada data.</td></tr>`;
    } else {
      pageData.forEach(function (d, i) {
        rows +=
          `<tr>` +
          `<td>${start + i + 1}</td>` +
          `<td>${d.pet_name || "-"}</td>` +
          `<td>${d.owner_name || "-"}</td>` +
          `<td>${d.branch_name}</td>` +
          `<td>${d.alasan || "-"}</td>` +
          `<td>${d.tanggal}</td>` +
          `</tr>`;
      });
    }
    $("#list-tidak-pengabaran").html(rows);

    // build pagination
    const $pg = $("#pagination-tidak-pengabaran");
    $pg.empty();
    if (totalPage <= 1) return;

    let showPage   = 5;
    let rangeMiddle = 3;
    let min, max;
    if (page <= rangeMiddle || totalPage <= showPage) {
      min = 1;
      max = Math.min(totalPage, showPage);
    } else {
      max = Math.min(page + rangeMiddle - 1, totalPage);
      min = max - (showPage - 1);
    }

    let html = `<li><a class="tp-arrow tp-left ${page === 1 ? "disabled" : ""}">«</a></li>`;
    for (let i = min; i <= max; i++) {
      html += `<li><a class="tp-num ${i === page ? "active" : ""}" data-p="${i}">${i}</a></li>`;
    }
    html += `<li><a class="tp-arrow tp-right ${page === totalPage ? "disabled" : ""}">»</a></li>`;
    $pg.html(html);

    $pg.find(".tp-left").on("click", function () {
      if (page > 1) renderTidakPengabaranTable(page - 1);
    });
    $pg.find(".tp-right").on("click", function () {
      if (page < totalPage) renderTidakPengabaranTable(page + 1);
    });
    $pg.find(".tp-num").on("click", function () {
      renderTidakPengabaranTable(parseInt($(this).data("p")));
    });
  }
  // ── End Tidak Pengabaran ───────────────────────────────────────────────────

  // Hide tidak-pengabaran section when Pet Shop tab is active
  $('a[href="#pet_shop"]').on("shown.bs.tab", function () {
    $(".tidak-pengabaran").hide();
  });
  $('a[href="#clinic"]').on("shown.bs.tab", function () {
    if (role === "admin" || role === "dokter" || role === "resepsionis" || role === "paramedis") {
      $(".tidak-pengabaran").show();
    }
  });

  function loadCabang() {
    $.ajax({
      url: $(".baseUrl").val() + "/api/cabang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        optCabang += `<option value=''>Cabang</option>`;

        if (data.length) {
          for (let i = 0; i < data.length; i++) {
            optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
          }
        }
        $("#filterCabangRawatInap").append(optCabang);
        $("#filterCabangDaftarBarangLimit").append(optCabang);
        $("#filterCabangDaftarBarangLimitPet").append(optCabang);
        $("#filterCabangTidakPengabaran").append(optCabang);
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }
});
