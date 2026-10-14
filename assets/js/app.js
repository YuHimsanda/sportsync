document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});

document.addEventListener("click", (event) => {
  const button = event.target.closest(".password-toggle");
  if (!button) return;

  event.preventDefault();
  const input = button.closest(".password-field").querySelector("input");
  const isVisible = input.type === "text";
  input.type = isVisible ? "password" : "text";
  button.textContent = isVisible ? "Show" : "Hide";
  button.setAttribute("aria-label", `${isVisible ? "Show" : "Hide"} password`);
});
