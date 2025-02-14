window.addEventListener('load', () => {

    const greetings = ["Good Morning. ", "Good Afternoon. ", "Good Evening. "];
    const currentHour = new Date().getHours();
    
    let index = currentHour >= 12 && currentHour < 17 ? 1 : currentHour >= 17 ? 2 : 0;
    
    document.getElementById("greet").innerHTML = greetings[index];
    
    });

function copyrightDate() {
    var date = new Date();
    var year = date.getFullYear();
    document.getElementById("curYear").innerHTML = year;
}

// console.log()

window.onscroll = scrollFunction;
    

function scrollFunction() {
    if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
      document.getElementById("navbar").style.display = "none";
      document.getElementById("menu").style.display = "none";      
    //   document.getElementById("back-to-top").style.display = "inline-flex";
      document.getElementById("menu-font").style.display = "none";
      document.getElementById("social").style.justifyContent = "center";
    }
    else {
      document.getElementById("navbar").style.display = "inline-flex";
    //   document.getElementById("back-to-top").style.display = "none";
      document.getElementById("menu-font").style.display = "block";
    }
  }

  function myLinksView() {
    var menu = document.getElementById("back-to-top");
    if (menu.style.display === "inline-flex") {
      menu.style.display = "none";
    } else {
      menu.style.display = "inline-flex";
    }
  }