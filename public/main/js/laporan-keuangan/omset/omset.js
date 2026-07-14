$(document).ready(function () {
  let optCabang = "";
  const isNoBranch = role.toLowerCase() === "dokter" || role.toLowerCase() === "resepsionis";

  let paramUrlSetup = {
    periode    : "",
    branchId   : "",
    connection : "",
    startDate  : "",
    endDate    : "",
    startMonth : "",
    endMonth   : "",
    tahun      : "",
  };

  // Populate year select (tahunan)
  const currentYear = new Date().getFullYear();
  let yearOpts = '<option value="">-- Tahun --</option>';
  for (let y = currentYear; y >= currentYear - 10; y--) {
    yearOpts += `<option value="${y}">${y}</option>`;
  }
  $("#selectTahun").html(yearOpts);

  // Bootstrap datepicker – month range picker untuk bulanan
  const bulanDpOptions = {
    format      : "mm-yyyy",
    startView   : "months",
    minViewMode : "months",
    autoclose   : true,
  };
  $("#inputBulanFrom").datepicker(bulanDpOptions);
  $("#inputBulanTo").datepicker(bulanDpOptions);

  $("#inputBulanFrom, #addonBulanFrom").on("click", function () {
    $("#inputBulanFrom").datepicker("show");
  });
  $("#inputBulanTo, #addonBulanTo").on("click", function () {
    $("#inputBulanTo").datepicker("show");
  });

  $("#inputBulanFrom").on("changeDate", function () {
    const d = $(this).datepicker("getDate");
    if (d) {
      const mm   = String(d.getMonth() + 1).padStart(2, "0");
      const yyyy = String(d.getFullYear());
      paramUrlSetup.startMonth = `${yyyy}-${mm}`;
      tryLoad();
    }
  });
  $("#inputBulanTo").on("changeDate", function () {
    const d = $(this).datepicker("getDate");
    if (d) {
      const mm   = String(d.getMonth() + 1).padStart(2, "0");
      const yyyy = String(d.getFullYear());
      paramUrlSetup.endMonth = `${yyyy}-${mm}`;
      tryLoad();
    }
  });

  if (isNoBranch) {
    $("#filterCabang").hide();
    $(".section-right-box-title").css("width", "unset");
  } else {
    $("#filterCabang").select2({ placeholder: "Cabang", allowClear: true });
    loadCabang();
  }

  // Tombol filter periode
  $("#periode-omset-group button").on("click", function () {
    $("#periode-omset-group button").removeClass("btn-info active").addClass("btn-default");
    $(this).removeClass("btn-default").addClass("btn-info active");
    paramUrlSetup.periode = $(this).data("periode");
    resetDateParams();
    showDateFilter(paramUrlSetup.periode);
    tryLoad();
  });

  // Filter cabang
  $("#filterCabang").on("select2:select", function () {
    onFilterCabang($(this).val());
  });
  $("#filterCabang").on("select2:unselect", function () {
    onFilterCabang($(this).val());
  });

  // Mingguan: daterangepicker (diinisialisasi saat periode dipilih)
  function initMingguanDatepicker() {
    const $input = $("#datepicker-omset-range");
    $input.val("");

    if ($input.data("daterangepicker")) {
      $input.data("daterangepicker").remove();
    }
    $input.off("apply.daterangepicker cancel.daterangepicker");

    $input.daterangepicker({
      autoUpdateInput : false,
      autoApply       : false,
      linkedCalendars : true,
      opens           : "right",
      locale: {
        format      : "YYYY-MM-DD",
        applyLabel  : "Terapkan",
        cancelLabel : "Batal",
        fromLabel   : "Dari",
        toLabel     : "Sampai",
        daysOfWeek  : ["Min","Sen","Sel","Rab","Kam","Jum","Sab"],
        monthNames  : ["Januari","Februari","Maret","April","Mei","Juni",
                       "Juli","Agustus","September","Oktober","November","Desember"],
        firstDay    : 1,
      },
    });

    $input.on("apply.daterangepicker", function (ev, picker) {
      paramUrlSetup.startDate = picker.startDate.format("YYYY-MM-DD");
      paramUrlSetup.endDate   = picker.endDate.format("YYYY-MM-DD");
      $input.val(paramUrlSetup.startDate + " - " + paramUrlSetup.endDate);
      tryLoad();
    });

    $input.on("cancel.daterangepicker", function () {
      $input.val("");
      paramUrlSetup.startDate = "";
      paramUrlSetup.endDate   = "";
    });

    // calendar icon juga buka picker
    $("#filter-mingguan .input-group-addon").off("click.drp").on("click.drp", function () {
      $input.trigger("click");
    });
  }

  // Tahunan: tahun
  $("#selectTahun").on("change", function () {
    paramUrlSetup.tahun = $(this).val();
    tryLoad();
  });

  function onFilterCabang(value) {
    paramUrlSetup.branchId   = value ? value.split("-")[0] : "";
    paramUrlSetup.connection = value ? value.split("-")[1] : "";
    tryLoad();
  }

  function showDateFilter(periode) {
    $(".filter-periode-input").css("display", "none");
    if (periode === "mingguan") {
      $("#filter-mingguan").css("display", "flex");
      initMingguanDatepicker();
      if (!isNoBranch) {
        $("#filterCabang").val(null).trigger("change");
        paramUrlSetup.branchId   = "";
        paramUrlSetup.connection = "";
        $("#cabang-filter-wrapper").hide();
      }
    } else if (periode === "bulanan") {
      $("#filter-bulanan").css("display", "flex");
      if (!isNoBranch) {
        $("#filterCabang").val(null).trigger("change");
        paramUrlSetup.branchId   = "";
        paramUrlSetup.connection = "";
        $("#cabang-filter-wrapper").hide();
      }
    } else if (periode === "tahunan") {
      $("#filter-tahunan").css("display", "flex");
      if (!isNoBranch) {
        $("#filterCabang").val(null).trigger("change");
        paramUrlSetup.branchId   = "";
        paramUrlSetup.connection = "";
        $("#cabang-filter-wrapper").hide();
      }
    } else if (periode === "sejak_dibuka") {
      if (!isNoBranch) {
        $("#filterCabang").val(null).trigger("change");
        paramUrlSetup.branchId   = "";
        paramUrlSetup.connection = "";
        $("#cabang-filter-wrapper").hide();
      }
    } else {
      if (!isNoBranch) {
        $("#cabang-filter-wrapper").show();
      }
    }
  }

  function resetDateParams() {
    paramUrlSetup.startDate  = "";
    paramUrlSetup.endDate    = "";
    paramUrlSetup.startMonth = "";
    paramUrlSetup.endMonth   = "";
    paramUrlSetup.tahun      = "";

    const drp = $("#datepicker-omset-range").data("daterangepicker");
    if (drp) drp.remove();
    $("#datepicker-omset-range").val("");

    $("#inputBulanFrom").val("").datepicker("update", "");
    $("#inputBulanTo").val("").datepicker("update", "");
    $("#selectTahun").val("");
  }

  function isDateReady() {
    const p = paramUrlSetup.periode;
    if (p === "mingguan")     return !!(paramUrlSetup.startDate && paramUrlSetup.endDate);
    if (p === "bulanan")      return !!(paramUrlSetup.startMonth && paramUrlSetup.endMonth);
    if (p === "tahunan")      return !!paramUrlSetup.tahun;
    if (p === "sejak_dibuka") return true;
    return false;
  }

  function canLoad() {
    if (!isDateReady()) return false;
    const noBranchRequired = ["mingguan", "bulanan", "tahunan", "sejak_dibuka"];
    if (!noBranchRequired.includes(paramUrlSetup.periode) && !isNoBranch && !paramUrlSetup.branchId) return false;
    return true;
  }

  function tryLoad() {
    $("#btn-export-omset").hide();
    if (canLoad()) loadAll();
  }

  function buildParam() {
    const p    = paramUrlSetup.periode;
    const base = { periode: p };

    if (p === "mingguan") {
      base.start_date = paramUrlSetup.startDate;
      base.end_date   = paramUrlSetup.endDate;
    } else if (p === "bulanan") {
      base.start_month = paramUrlSetup.startMonth;
      base.end_month   = paramUrlSetup.endMonth;
    } else if (p === "tahunan") {
      base.tahun = paramUrlSetup.tahun;
    }
    // sejak_dibuka: tidak perlu parameter tambahan
    return base;
  }

  function loadAll() {
    loadLaporanKeuanganOmset();
  }

  // Tombol Generate Excel
  $("#btn-export-omset").on("click", function () {
    $.ajax({
      url      : $(".baseUrl").val() + "/api/laporan-keuangan/rekap-all/export",
      headers  : { Authorization: `Bearer ${token}` },
      type     : "GET",
      data     : buildParam(),
      xhrFields: { responseType: "blob" },
      beforeSend: function () { $("#loading-screen").show(); },
      success: function (data, status, xhr) {
        const disposition = xhr.getResponseHeader("content-disposition");
        const matches     = /"([^"]*)"/.exec(disposition);
        const filename    = matches != null && matches[1] ? matches[1] : "rekap-omset.xlsx";
        const blob        = new Blob([data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const downloadUrl = URL.createObjectURL(blob);
        const a           = document.createElement("a");
        a.href            = downloadUrl;
        a.download        = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(downloadUrl);
      },
      complete: function () { $("#loading-screen").hide(); },
      error: function (err) {
        if (err.status === 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  });

  function loadLaporanKeuanganOmset() {
    $.ajax({
      url    : $(".baseUrl").val() + "/api/laporan-keuangan/rekap-all",
      headers: { Authorization: `Bearer ${token}` },
      type   : "GET",
      data   : buildParam(),
      beforeSend: function () { $("#loading-screen").show(); },
      success: function (resp) {
        const getData = resp.datas;
        const headers = resp.branches;
        let bodyHTML  = "";
        let headHTML  = "";

        $("#list-laporan-keuangan-omset tr").remove();
        $("#head-laporan-keuangan-omset tr").remove();

        if (paramUrlSetup.branchId) {
          headHTML =
            `<tr>` +
            `<th>No</th>` +
            `<th>Periode</th>` +
            `<th>Total Omset (Rp)</th>` +
            `</tr>`;

          if (getData.length) {
            $.each(getData, function (idx, v) {
              bodyHTML +=
                `<tr>` +
                `<td>${++idx}</td>` +
                `<td>${v.dates}</td>` +
                `<td>${Number(v.total_omset || 0).toLocaleString("id-ID")}</td>` +
                `</tr>`;
            });
          } else {
            bodyHTML = `<tr class="text-center"><td colspan="3">Tidak ada data.</td></tr>`;
          }
        } else {
          let thHTML = "";
          headers.forEach((branch) => {
            thHTML += `<th>${branch.branch_name}</th>`;
          });

          headHTML =
            `<tr>` +
            `<th>No</th>` +
            `<th>Periode</th>` +
            `<th>Total Omset (Rp)</th>` +
            thHTML +
            `</tr>`;

          if (getData.length) {
            const fixedKeys  = ["dates", "total_omset"];
            const branchKeys = Object.keys(getData[0]).filter((k) => !fixedKeys.includes(k));

            $.each(getData, function (idx, v) {
              let row = `<tr>`;
              row += `<td>${++idx}</td>`;
              row += `<td>${v.dates}</td>`;
              row += `<td>${Number(v.total_omset || 0).toLocaleString("id-ID")}</td>`;
              branchKeys.forEach((key) => {
                row += `<td>${Number(v[key] || 0).toLocaleString("id-ID")}</td>`;
              });
              row += `</tr>`;
              bodyHTML += row;
            });
          } else {
            bodyHTML = `<tr class="text-center"><td colspan="3">Tidak ada data.</td></tr>`;
          }
        }

        $("#head-laporan-keuangan-omset").append(headHTML);
        $("#list-laporan-keuangan-omset").append(bodyHTML);
        $("#btn-export-omset").show();
        renderOmsetChart(getData, headers);
      },
      complete: function () { $("#loading-screen").hide(); },
      error: function (err) {
        if (err.status === 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadCabang() {
    $.ajax({
      url    : $(".baseUrl").val() + "/api/cabang/all",
      headers: { Authorization: `Bearer ${token}` },
      type   : "GET",
      beforeSend: function () { $("#loading-screen").show(); },
      success: function (data) {
        optCabang += `<option value=''>Cabang</option>`;
        if (data.length) {
          data.forEach(function (item) {
            optCabang += `<option value="${item.id}-${item.connection}">${item.branch_name}</option>`;
          });
        }
        $("#filterCabang").append(optCabang);
      },
      complete: function () { $("#loading-screen").hide(); },
      error: function (err) {
        if (err.status === 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function renderOmsetChart(getData, branches) {
    const categories    = getData.map(d => d.dates);
    const isMultiBranch = branches && branches.length > 1;

    let series, tooltipConfig;

    if (isMultiBranch) {
      series = branches.map(b => ({
        name: b.branch_name,
        data: getData.map(d => d[b.branch_slug] || 0),
      }));
      tooltipConfig = {
        shared     : true,
        headerFormat: "<b>{point.x}</b><br/>",
        pointFormatter: function () {
          return `${this.series.name}: <b>Rp ${Number(this.y).toLocaleString("id-ID")}</b><br/>`;
        },
      };
    } else {
      series = [{
        name: "Omset",
        data: getData.map(d => d.total_omset),
      }];
      tooltipConfig = {
        headerFormat : "<b>{point.x}</b><br/>",
        pointFormatter: function () {
          return `Omset: <b>Rp ${Number(this.y).toLocaleString("id-ID")}</b>`;
        },
      };
    }

    Highcharts.chart("rekapWidgetOmset", {
      chart  : { type: "line" },
      title  : { text: "" },
      xAxis  : { categories: categories },
      yAxis  : {
        title: { text: "Nominal (Rp)" },
        labels: {
          formatter: function () {
            return Number(this.value).toLocaleString("id-ID");
          },
        },
      },
      legend : { enabled: isMultiBranch },
      credits: { enabled: false },
      plotOptions: {
        line: {
          marker    : { enabled: true, radius: 4 },
          dataLabels: { enabled: false },
        },
      },
      tooltip: tooltipConfig,
      series : series,
    });
  }
});
