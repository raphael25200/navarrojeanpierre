import imagemin from "imagemin";
import mozjpeg from "imagemin-mozjpeg";
import pngquant from "imagemin-pngquant";
import gifsicle from "imagemin-gifsicle";
import svgo from "imagemin-svgo";
import fs from "fs";
import glob from "glob";

(async () => {
  try {
    // Recherche tous les fichiers images, tous niveaux de sous-dossiers
    const files = glob.sync(
      "assets/images/**/*.{jpg,jpeg,png,gif,svg,JPG,JPEG,PNG,GIF,SVG}",
      {
        nodir: true,
      }
    );

    for (const filePath of files) {
      const buffer = fs.readFileSync(filePath);
      const optimizedBuffer = await imagemin.buffer(buffer, {
        plugins: [
          mozjpeg({ quality: 75 }),
          pngquant({ quality: [0.65, 0.9] }),
          gifsicle({ interlaced: false }),
          svgo(),
        ],
      });

      // Écrase le fichier original
      fs.writeFileSync(filePath, optimizedBuffer);
    }

    console.log("✅ Toutes les images ont été optimisées directement !");
  } catch (err) {
    console.error("❌ Erreur lors de l'optimisation :", err);
  }
})();
