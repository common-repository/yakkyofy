document.addEventListener("DOMContentLoaded", (event) => {
	if (document.querySelectorAll("#report-toggle").length > 0) {
		document
			.getElementById("report-toggle")
			.addEventListener("click", function (e) {
				document.getElementById("yak-report").classList.toggle("yak-show");
				e.preventDefault();
			});
	}
	if (document.querySelectorAll(".yak-reset").length > 0) {
		document
			.getElementsByClassName("yak-reset")[0]
			.addEventListener("click", function (e) {
				window.location.href = window.location.href + "&yakkyofy_reset=1";
				e.preventDefault();
			});
	}
	if (document.querySelectorAll(".yak-login").length > 0) {
		document.addEventListener("mousemove", function() {window.onbeforeunload = null});
	}
});
