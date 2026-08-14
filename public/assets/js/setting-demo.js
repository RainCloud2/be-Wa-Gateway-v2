"use strict";

$(window).resize(function () {
  $(window).width();
});

getCheckmark();
loadSavedColors();

function saveColor(key, value) {
  if (value === "default") {
    localStorage.removeItem(key);
  } else {
    localStorage.setItem(key, value);
  }
}

function applyColor(key, target, cssFn) {
  var saved = localStorage.getItem(key);
  if (!saved) return;
  if (saved === "default") {
    $(target).removeAttr("data-background-color");
  } else {
    $(target).attr("data-background-color", saved);
  }
  if (cssFn) cssFn();
}

function loadSavedColors() {
  applyColor("logoHeaderColor", ".logo-header", function () {
    customCheckColor();
    layoutsColors();
  });
  applyColor("topbarColor", ".main-header .navbar-header", function () {
    layoutsColors();
  });
  applyColor("sidebarColor", ".sidebar", function () {
    layoutsColors();
  });
}

$(".changeBodyBackgroundFullColor").on("click", function () {
  var color = $(this).attr("data-color");
  if (color == "default") {
    $("body").removeAttr("data-background-full");
  } else {
    $("body").attr("data-background-full", $(this).attr("data-color"));
  }
  $(this).parent().find(".changeBodyBackgroundFullColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
});

$(".changeLogoHeaderColor").on("click", function () {
  var color = $(this).attr("data-color");
  if (color == "default") {
    $(".logo-header").removeAttr("data-background-color");
  } else {
    $(".logo-header").attr("data-background-color", color);
  }
  saveColor("logoHeaderColor", color);
  $(this).parent().find(".changeLogoHeaderColor").removeClass("selected");
  $(this).addClass("selected");
  customCheckColor();
  layoutsColors();
  getCheckmark();
});

$(".changeTopBarColor").on("click", function () {
  var color = $(this).attr("data-color");
  if (color == "default") {
    $(".main-header .navbar-header").removeAttr("data-background-color");
  } else {
    $(".main-header .navbar-header").attr("data-background-color", color);
  }
  saveColor("topbarColor", color);
  $(this).parent().find(".changeTopBarColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
});

$(".changeSideBarColor").on("click", function () {
  var color = $(this).attr("data-color");
  if (color == "default") {
    $(".sidebar").removeAttr("data-background-color");
  } else {
    $(".sidebar").attr("data-background-color", color);
  }
  saveColor("sidebarColor", color);
  $(this).parent().find(".changeSideBarColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
});

$(".changeBackgroundColor").on("click", function () {
  $("body").removeAttr("data-background-color");
  $("body").attr("data-background-color", $(this).attr("data-color"));
  $(this).parent().find(".changeBackgroundColor").removeClass("selected");
  $(this).addClass("selected");
  getCheckmark();
});

function customCheckColor() {
  var color = $(".logo-header").attr("data-background-color") || "";
  var lightColors = ["white", "light-blue", "green", "orange", "red", "light-blue2", "green2", "orange2", "red2"];
  var logo = lightColors.indexOf(color) !== -1 ? "logo_dark.svg" : "logo_light.svg";
  $(".logo-header .navbar-brand").attr("src", "/assets/img/kaiadmin/" + logo + "?v=" + Date.now());
}

var toggle_customSidebar = false,
  custom_open = 0;

if (!toggle_customSidebar) {
  var toggle = $(".custom-template .custom-toggle");
  toggle.on("click", function () {
    if (custom_open == 1) {
      $(".custom-template").removeClass("open");
      toggle.removeClass("toggled");
      custom_open = 0;
    } else {
      $(".custom-template").addClass("open");
      toggle.addClass("toggled");
      custom_open = 1;
    }
  });
  toggle_customSidebar = true;
}

function getCheckmark() {
  var checkmark = '<i class="gg-check"></i>';
  $(".btnSwitch").find("button").empty();
  $(".btnSwitch").find("button.selected").append(checkmark);
}
