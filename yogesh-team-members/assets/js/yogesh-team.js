jQuery(document).ready(function ($) {
  function loadMembers(page = 1, search = "") {
    const widget = $(".yogesh-team-widget");
    const perPage = widget.data("per-page");

    $.ajax({
      url: yogeshTeamData.apiUrl,
      data: { page, per_page: perPage, search },
      beforeSend: function () {
        $(".yogesh-team-grid").html("<p>Loading...</p>");
      },
      success: function (response) {
        const grid = $(".yogesh-team-grid");
        grid.empty();

        if (response.members && response.members.length) {
          response.members.forEach((member) => {
            grid.append(`
              <div class="team-item">
                <img src="${member.photo}" alt="${member.full_name}" />
                <h3>${member.full_name}</h3>
                <p class="role">${member.role}</p>
                <p class="email">${member.email}</p>
                <p class="skills">${member.skills.join(", ")}</p>
              </div>
            `);
          });
        } else {
          grid.html("<p>No members found.</p>");
        }

        $(".page-info").text(`Page ${response.page} of ${Math.ceil(response.total / response.per_page)}`);
        $(".prev").prop("disabled", page <= 1);
        $(".next").prop("disabled", page >= Math.ceil(response.total / response.per_page));
      },
    });
  }

  loadMembers();

  $(".yogesh-team-search").on("keyup", function () {
    const val = $(this).val();
    loadMembers(1, val);
  });

  $(".prev").on("click", function () {
    let current = parseInt($(".page-info").text().match(/Page (\d+)/)[1]);
    if (current > 1) loadMembers(current - 1);
  });

  $(".next").on("click", function () {
    let current = parseInt($(".page-info").text().match(/Page (\d+)/)[1]);
    loadMembers(current + 1);
  });
});
