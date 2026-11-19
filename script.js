document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".delete-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      if (!confirm("Delete this task?")) {
        e.preventDefault();
      }
    });
  });

  var desc = document.getElementById("task_description");
  var counter = document.createElement("small");
  counter.textContent = desc.value.length + " / 255";
  desc.insertAdjacentElement("afterend", counter);

  desc.addEventListener("input", function () {
    counter.textContent = desc.value.length + " / 255";
  });

  var form = document.getElementById("task-form");
  form.addEventListener("submit", function (e) {
    var name = document.getElementById("task_name").value.trim();
    if (name === "") {
      alert("Task name can't be empty.");
      e.preventDefault();
    }
  });
});