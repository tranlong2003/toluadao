const form = document.getElementById("report-form");
const btn = form.querySelector("button");

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  btn.disabled = true;
  btn.textContent = "⏳ Đang gửi...";

  let formData = new FormData();
  formData.append("type", document.getElementById("type").value);
  formData.append("value", document.getElementById("value").value);
  formData.append("desc", document.getElementById("desc").value);

  let res = await fetch("backend/add_report.php", { method:"POST", body:formData });
  let txt = await res.text();

  if (txt.includes("success")) {
    document.getElementById("report-success").style.display="block";
    form.reset();
  } else {
    alert("❌ Gửi thất bại! Thử lại sau.");
  }

  btn.disabled = false;
  btn.textContent = "Gửi tố cáo";
});
