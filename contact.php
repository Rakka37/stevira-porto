<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact - Stevira Rachel Gabriella</title>

  <style>
    /*  GLOBAL  */
    *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
    :root{--bg1:#1b1d26;--bg2:#2d2a35;--accent:#f8c146;--panel:rgba(66,66,151,.7)}
    html,body{height:100%;display:flex;flex-direction:column}
    body{background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#eee;text-align:center}
    body>*:not(footer){flex-shrink:0}

    a{text-decoration:none;color:inherit}

    /*  NAVBAR  */
    .navbar{
      position:fixed;top:0;left:0;width:100%;
      background:var(--panel);
      backdrop-filter:blur(8px);
      box-shadow:0 3px 8px rgba(0,0,0,.4);
      z-index:10;
    }
    .nav-container{
      max-width:1100px;margin:auto;
      display:flex;align-items:center;justify-content:space-between;
      padding:10px 20px;
    }
    .logo{
      display:flex;align-items:center;gap:10px;
      color:var(--accent);font-weight:700;
    }
    .logo img{width:40px;height:40px;border-radius:50%;border:3px solid var(--accent);object-fit:cover}

    .menu{display:flex;gap:25px;list-style:none}
    .menu a{color:#eee;font-weight:500;transition:.18s}
    .menu a:hover{color:var(--accent);transform:scale(1.05)}

    .menu a.active{
      position:relative;color:#fff;
    }
    .menu a.active::after{
      content:"";position:absolute;left:0;right:0;bottom:-8px;
      height:4px;background:var(--accent);border-radius:4px;
    }

    .menu-icon{display:none;font-size:1.8rem;color:var(--accent);cursor:pointer}

    @media(max-width:768px){
      .menu{
        display:none;position:absolute;
        top:60px;left:0;width:100%;
        flex-direction:column;
        background:#111;padding:18px 0;
      }
      .menu.show{display:flex}
      .menu-icon{display:block}
    }

    /* hover underline pada ikon sosmed */
.contact-icons a{
  display:inline-block;
  padding-bottom:4px;
  border-bottom:4px solid transparent;
  transition:.2s;
}

.contact-icons a:hover{
  border-bottom:4px solid var(--accent);
}


    /* CONTACT SECTION */
    .contact-section{
      background:#000;
      margin-top:60px;
      padding:120px 16px 80px;
      border-bottom:4px solid var(--accent);
    }
    .section-title{
      color:var(--accent);
      font-size:2.1rem;font-weight:800;
      margin-bottom:28px;
    }

    .contact-box{
      background:#b7d600;
      max-width:460px;
      margin:0 auto;
      padding:26px;
      border-radius:18px;
      box-shadow:0 10px 30px rgba(0,0,0,.35);
    }
    .contact-icons img{
      width:52px;margin:0 18px;
      transition:.2s;
    }
    .contact-icons img:hover{transform:scale(1.12)}

    .contact-info{
      margin-top:14px;text-align:left;
    }
    .contact-info p{
      color:#000;font-size:.92rem;margin:6px 0;line-height:1.45;
    }

    /*FOOTER */
    footer{
      margin-top:auto;
      background:var(--panel);
      padding:20px 0 30px;
      color:#aaa;text-align:center;
      border-top:1px solid rgba(255,255,255,.1);
    }

    .login-admin{
      position:fixed;left:6px;bottom:8px;
      background:#0007;color:#ddd;
      font-size:7px;padding:8px 10px;
      border-radius:8px;text-decoration:none;
    }
    .login-admin:hover{color:#fff}
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="nav-container">

      <div class="logo">
        <img src="stevira.jpg" alt="Stevira">
        Stevira Rachel Gabriella
      </div>

      <span class="menu-icon" id="menu-icon">&#9776;</span>

      <ul class="menu" id="menu-list">
        <li><a href="index.php">Home</a></li>
        <li><a href="biodata.php">Biodata</a></li>
        <li><a href="project.php">Project</a></li>
        <li><a href="certificates.php">Certificate</a></li>
        <li><a href="sharing.php">Sharing</a></li>
        <li><a href="contact.php" class="active">Contact</a></li>
      </ul>

    </div>
  </nav>

  <!-- CONTACT SECTION -->
  <section class="contact-section">
    <h1 class="section-title">Contact</h1>

    <div class="contact-box">
      <div class="contact-icons">
        <a href="https://wa.me/628883049119">
          <img src="https://cdn-icons-png.flaticon.com/512/3536/3536445.png" alt="WhatsApp">
        </a>

        <a href="https://www.instagram.com/stevirachel?igsh=dDUxYjRzeDVueWYw">
          <img src="https://cdn-icons-png.flaticon.com/512/1384/1384063.png" alt="Instagram">
        </a>
      </div>

      <div class="contact-info">
        <p><b>📞 Telepon:</b> 0888-3049-119</p>
        <p><b>📍 Alamat:</b> Jl. Mutiara 6.3 No.2, Kota Baru Driyorejo, Gresik</p>
        <p><b>✉️ Email:</b> steviragabriella@gmail.com</p>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    © 2025 Stevira Rachel Gabriella | Portofolio UPNVJT
    <a class="login-admin" href="admin.php">Login Admin</a>
  </footer>

  <script>
    const menuIcon=document.getElementById('menu-icon');
    const menuList=document.getElementById('menu-list');
    menuIcon.onclick=()=>menuList.classList.toggle('show');
  </script>

</body>
</html>
