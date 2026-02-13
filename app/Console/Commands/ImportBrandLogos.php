<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportBrandLogos extends Command
{
    protected $signature = 'brands:import-logos';
    protected $description = 'Import brand logos from Shopify CDN URLs';

    private $brandData = [
        ["name" => "4711", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/4711.webp?v=1707312086", "url" => "/collections/4711"],
        ["name" => "007 Fragrances", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/007.webp?v=1707312112", "url" => "/collections/007-fragrances"],
        ["name" => "10 Corso Como", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/10-corso-como.webp?v=1707312138", "url" => "/collections/10-corso-como"],
        ["name" => "100BON", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/100-bon.webp?v=1707312158", "url" => "/collections/100bon"],
        ["name" => "18.21 Man Made", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/18-21-man-made.webp?v=1707312181", "url" => "/collections/18-21-man-made"],
        ["name" => "19-69", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/19-69.webp?v=1707312204", "url" => "/collections/19-69"],
        ["name" => "27 87", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/27-87.webp?v=1707312227", "url" => "/collections/27-87"],
        ["name" => "3 Hundred Thirty 7", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/3-hundred-thirty-7.webp?v=1707312249", "url" => "/collections/3-hundred-thirty-7"],
        ["name" => "37 FRAMES", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/37-frames.webp?v=1707312272", "url" => "/collections/37-frames"],
        ["name" => "3W Clinic", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/3w-clinic.webp?v=1707312297", "url" => "/collections/3w-clinic"],
        ["name" => "5th Avenue NYC", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/5th-avenue-nyc.webp?v=1707312321", "url" => "/collections/5th-avenue-nyc"],
        ["name" => "777", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/777.webp?v=1707312344", "url" => "/collections/777"],
        ["name" => "8 Greens", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/8-greens.webp?v=1707312366", "url" => "/collections/8-greens"],
        ["name" => "A Lab on Fire", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/a-lab-on-fire.webp?v=1707312388", "url" => "/collections/a-lab-on-fire"],
        ["name" => "Abercrombie & Fitch", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/abercrombie-fitch.webp?v=1707312410", "url" => "/collections/abercrombie-fitch"],
        ["name" => "Acqua Dell'Elba", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/acqua-dell-elba.webp?v=1707312432", "url" => "/collections/acqua-dell-elba"],
        ["name" => "Acqua Di Parma", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/acqua-di-parma.webp?v=1707312454", "url" => "/collections/acqua-di-parma"],
        ["name" => "Adidas", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/adidas.webp?v=1707312476", "url" => "/collections/adidas"],
        ["name" => "Adolfo Dominguez", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/adolfo-dominguez.webp?v=1707312498", "url" => "/collections/adolfo-dominguez"],
        ["name" => "Aerin", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/aerin.webp?v=1707312519", "url" => "/collections/aerin"],
        ["name" => "Affinessence", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/affinessence.webp?v=1707312542", "url" => "/collections/affinessence"],
        ["name" => "Afnan", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/afnan.webp?v=1707312564", "url" => "/collections/afnan"],
        ["name" => "Agent Provocateur", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/agent-provocateur.webp?v=1707312587", "url" => "/collections/agent-provocateur"],
        ["name" => "Aigner", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/aigner.webp?v=1707312610", "url" => "/collections/aigner"],
        ["name" => "Ajmal", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ajmal.webp?v=1707312633", "url" => "/collections/ajmal"],
        ["name" => "Al Haramain", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/al-haramain.webp?v=1707312655", "url" => "/collections/al-haramain"],
        ["name" => "Alaia Paris", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alaia-paris.webp?v=1707312678", "url" => "/collections/alaia-paris"],
        ["name" => "Alaïa", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alaia.webp?v=1707312700", "url" => "/collections/alaia"],
        ["name" => "Alan Bray", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alan-bray.webp?v=1707312722", "url" => "/collections/alan-bray"],
        ["name" => "Alexander McQueen", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alexander-mcqueen.webp?v=1707312744", "url" => "/collections/alexander-mcqueen"],
        ["name" => "Alexandre.J", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alexandre-j.webp?v=1707312767", "url" => "/collections/alexandre-j"],
        ["name" => "Alfred Dunhill", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alfred-dunhill.webp?v=1707312789", "url" => "/collections/alfred-dunhill"],
        ["name" => "Alfred Sung", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/alfred-sung.webp?v=1707312811", "url" => "/collections/alfred-sung"],
        ["name" => "Amazingy", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/amazingy.webp?v=1707312834", "url" => "/collections/amazingy"],
        ["name" => "Amouage", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/amouage.webp?v=1707312856", "url" => "/collections/amouage"],
        ["name" => "Annayake", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/annayake.webp?v=1707312878", "url" => "/collections/annayake"],
        ["name" => "Anne Klein", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/anne-klein.webp?v=1707312900", "url" => "/collections/anne-klein"],
        ["name" => "Annick Goutal", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/annick-goutal.webp?v=1707312923", "url" => "/collections/annick-goutal"],
        ["name" => "Antonio Banderas", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/antonio-banderas.webp?v=1707312945", "url" => "/collections/antonio-banderas"],
        ["name" => "Aramis", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/aramis.webp?v=1707312968", "url" => "/collections/aramis"],
        ["name" => "Ariana Grande", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ariana-grande.webp?v=1707312991", "url" => "/collections/ariana-grande"],
        ["name" => "Ariana Grande Fragrances", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ariana-grande-fragrances.webp?v=1707313013", "url" => "/collections/ariana-grande-fragrances"],
        ["name" => "Armaf", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/armaf.webp?v=1707313036", "url" => "/collections/armaf"],
        ["name" => "Armand Basi", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/armand-basi.webp?v=1707313058", "url" => "/collections/armand-basi"],
        ["name" => "Armani", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/armani.webp?v=1707313081", "url" => "/collections/armani"],
        ["name" => "Arsenal", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/arsenal.webp?v=1707313103", "url" => "/collections/arsenal"],
        ["name" => "Artisan Pure", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/artisan-pure.webp?v=1707313126", "url" => "/collections/artisan-pure"],
        ["name" => "Atelier Cologne", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/atelier-cologne.webp?v=1707313148", "url" => "/collections/atelier-cologne"],
        ["name" => "Atkinsons", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/atkinsons.webp?v=1707313171", "url" => "/collections/atkinsons"],
        ["name" => "Azzaro", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/azzaro.webp?v=1707313193", "url" => "/collections/azzaro"],
        ["name" => "Baccarat", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/baccarat.webp?v=1707313216", "url" => "/collections/baccarat"],
        ["name" => "Balenciaga", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/balenciaga.webp?v=1707313238", "url" => "/collections/balenciaga"],
        ["name" => "Balmain", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/balmain.webp?v=1707313261", "url" => "/collections/balmain"],
        ["name" => "Banana Republic", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/banana-republic.webp?v=1707313283", "url" => "/collections/banana-republic"],
        ["name" => "Banderas", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/banderas.webp?v=1707313306", "url" => "/collections/banderas"],
        ["name" => "Barbie", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/barbie.webp?v=1707313328", "url" => "/collections/barbie"],
        ["name" => "Benigna Parfums", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/benigna-parfums.webp?v=1707313351", "url" => "/collections/benigna-parfums"],
        ["name" => "Bentley", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/bentley.webp?v=1707313373", "url" => "/collections/bentley"],
        ["name" => "Berluti", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/berluti.webp?v=1707313396", "url" => "/collections/berluti"],
        ["name" => "Betty Barclay", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/betty-barclay.webp?v=1707313418", "url" => "/collections/betty-barclay"],
        ["name" => "Beyonce", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/beyonce.webp?v=1707313441", "url" => "/collections/beyonce"],
        ["name" => "Boucheron", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/boucheron.webp?v=1707313463", "url" => "/collections/boucheron"],
        ["name" => "Britney Spears", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/britney-spears.webp?v=1707313486", "url" => "/collections/britney-spears"],
        ["name" => "Burberry", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/burberry.webp?v=1707313508", "url" => "/collections/burberry"],
        ["name" => "Bvlgari", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/bvlgari.webp?v=1707313531", "url" => "/collections/bvlgari"],
        ["name" => "By Kilian", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/by-kilian.webp?v=1707313553", "url" => "/collections/by-kilian"],
        ["name" => "By Terry", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/by-terry.webp?v=1707313576", "url" => "/collections/by-terry"],
        ["name" => "Byredo", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/byredo.webp?v=1707313598", "url" => "/collections/byredo"],
        ["name" => "Cacharel", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/cacharel.webp?v=1707313621", "url" => "/collections/cacharel"],
        ["name" => "Calvin Klein", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/calvin-klein.webp?v=1707313643", "url" => "/collections/calvin-klein"],
        ["name" => "Carner Barcelona", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/carner-barcelona.webp?v=1707313666", "url" => "/collections/carner-barcelona"],
        ["name" => "Carolina Herrera", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/carolina-herrera.webp?v=1707313688", "url" => "/collections/carolina-herrera"],
        ["name" => "Carven", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/carven.webp?v=1707313711", "url" => "/collections/carven"],
        ["name" => "Celine", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/celine.webp?v=1707313733", "url" => "/collections/celine"],
        ["name" => "Celine Dion", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/celine-dion.webp?v=1707313756", "url" => "/collections/celine-dion"],
        ["name" => "Cerruti", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/cerruti.webp?v=1707313778", "url" => "/collections/cerruti"],
        ["name" => "Chanel", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/chanel.webp?v=1707313801", "url" => "/collections/chanel"],
        ["name" => "Chloé", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/chloe.webp?v=1707313823", "url" => "/collections/chloe"],
        ["name" => "Chopard", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/chopard.webp?v=1707313846", "url" => "/collections/chopard"],
        ["name" => "Christian Dior", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/christian-dior.webp?v=1707313868", "url" => "/collections/christian-dior"],
        ["name" => "Christian Lacroix", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/christian-lacroix.webp?v=1707313891", "url" => "/collections/christian-lacroix"],
        ["name" => "Christina Aguilera", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/christina-aguilera.webp?v=1707313913", "url" => "/collections/christina-aguilera"],
        ["name" => "Clive Christian", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/clive-christian.webp?v=1707313936", "url" => "/collections/clive-christian"],
        ["name" => "Coach", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/coach.webp?v=1707313958", "url" => "/collections/coach"],
        ["name" => "Comptoir Sud Pacifique", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/comptoir-sud-pacifique.webp?v=1707313981", "url" => "/collections/comptoir-sud-pacifique"],
        ["name" => "Costume National", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/costume-national.webp?v=1707314003", "url" => "/collections/costume-national"],
        ["name" => "Creed", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/creed.webp?v=1707314026", "url" => "/collections/creed"],
        ["name" => "D&G", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/d-g.webp?v=1707314048", "url" => "/collections/d-g"],
        ["name" => "Davidoff", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/davidoff.webp?v=1707314071", "url" => "/collections/davidoff"],
        ["name" => "Diesel", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/diesel.webp?v=1707314093", "url" => "/collections/diesel"],
        ["name" => "Diptyque", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/diptyque.webp?v=1707314116", "url" => "/collections/diptyque"],
        ["name" => "DKNY", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/dkny.webp?v=1707314138", "url" => "/collections/dkny"],
        ["name" => "Dolce & Gabbana", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/dolce-gabbana.webp?v=1707314161", "url" => "/collections/dolce-gabbana"],
        ["name" => "Donna Karan", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/donna-karan.webp?v=1707314183", "url" => "/collections/donna-karan"],
        ["name" => "Dsquared2", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/dsquared2.webp?v=1707314206", "url" => "/collections/dsquared2"],
        ["name" => "Dunhill", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/dunhill.webp?v=1707314228", "url" => "/collections/dunhill"],
        ["name" => "Elizabeth Arden", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/elizabeth-arden.webp?v=1707314251", "url" => "/collections/elizabeth-arden"],
        ["name" => "Escada", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/escada.webp?v=1707314273", "url" => "/collections/escada"],
        ["name" => "Estée Lauder", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/estee-lauder.webp?v=1707314296", "url" => "/collections/estee-lauder"],
        ["name" => "Etro", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/etro.webp?v=1707314318", "url" => "/collections/etro"],
        ["name" => "Ferrari", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ferrari.webp?v=1707314341", "url" => "/collections/ferrari"],
        ["name" => "Franck Olivier", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/franck-olivier.webp?v=1707314363", "url" => "/collections/franck-olivier"],
        ["name" => "Frédéric Malle", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/frederic-malle.webp?v=1707314386", "url" => "/collections/frederic-malle"],
        ["name" => "Givenchy", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/givenchy.webp?v=1707314408", "url" => "/collections/givenchy"],
        ["name" => "Giorgio Armani", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/giorgio-armani.webp?v=1707314431", "url" => "/collections/giorgio-armani"],
        ["name" => "Gucci", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/gucci.webp?v=1707314453", "url" => "/collections/gucci"],
        ["name" => "Guerlain", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/guerlain.webp?v=1707314476", "url" => "/collections/guerlain"],
        ["name" => "Guess", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/guess.webp?v=1707314498", "url" => "/collections/guess"],
        ["name" => "Hermès", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/hermes.webp?v=1707314521", "url" => "/collections/hermes"],
        ["name" => "Hugo Boss", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/hugo-boss.webp?v=1707314543", "url" => "/collections/hugo-boss"],
        ["name" => "Initio", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/initio.webp?v=1707314566", "url" => "/collections/initio"],
        ["name" => "Issey Miyake", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/issey-miyake.webp?v=1707314588", "url" => "/collections/issey-miyake"],
        ["name" => "Jean Paul Gaultier", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/jean-paul-gaultier.webp?v=1707314611", "url" => "/collections/jean-paul-gaultier"],
        ["name" => "Jimmy Choo", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/jimmy-choo.webp?v=1707314633", "url" => "/collections/jimmy-choo"],
        ["name" => "Jo Malone", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/jo-malone.webp?v=1707314656", "url" => "/collections/jo-malone"],
        ["name" => "Juicy Couture", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/juicy-couture.webp?v=1707314678", "url" => "/collections/juicy-couture"],
        ["name" => "Kenzo", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/kenzo.webp?v=1707314701", "url" => "/collections/kenzo"],
        ["name" => "Lacoste", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/lacoste.webp?v=1707314723", "url" => "/collections/lacoste"],
        ["name" => "Lalique", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/lalique.webp?v=1707314746", "url" => "/collections/lalique"],
        ["name" => "Lancôme", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/lancome.webp?v=1707314768", "url" => "/collections/lancome"],
        ["name" => "Lattafa", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/lattafa.webp?v=1707314791", "url" => "/collections/lattafa"],
        ["name" => "Le Labo", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/le-labo.webp?v=1707314813", "url" => "/collections/le-labo"],
        ["name" => "Loewe", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/loewe.webp?v=1707314836", "url" => "/collections/loewe"],
        ["name" => "Louis Vuitton", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/louis-vuitton.webp?v=1707314858", "url" => "/collections/louis-vuitton"],
        ["name" => "Marc Jacobs", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/marc-jacobs.webp?v=1707314881", "url" => "/collections/marc-jacobs"],
        ["name" => "Maison Francis Kurkdjian", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/maison-francis-kurkdjian.webp?v=1707314903", "url" => "/collections/maison-francis-kurkdjian"],
        ["name" => "Mancera", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/mancera.webp?v=1707314926", "url" => "/collections/mancera"],
        ["name" => "Maison Margiela", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/maison-margiela.webp?v=1707314948", "url" => "/collections/maison-margiela"],
        ["name" => "Memo Paris", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/memo-paris.webp?v=1707314971", "url" => "/collections/memo-paris"],
        ["name" => "Michael Kors", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/michael-kors.webp?v=1707314993", "url" => "/collections/michael-kors"],
        ["name" => "Missoni", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/missoni.webp?v=1707315016", "url" => "/collections/missoni"],
        ["name" => "Miu Miu", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/miu-miu.webp?v=1707315038", "url" => "/collections/miu-miu"],
        ["name" => "Molinard", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/molinard.webp?v=1707315061", "url" => "/collections/molinard"],
        ["name" => "Montale", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/montale.webp?v=1707315083", "url" => "/collections/montale"],
        ["name" => "Montblanc", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/montblanc.webp?v=1707315106", "url" => "/collections/montblanc"],
        ["name" => "Moschino", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/moschino.webp?v=1707315128", "url" => "/collections/moschino"],
        ["name" => "Mugler", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/mugler.webp?v=1707315151", "url" => "/collections/mugler"],
        ["name" => "Narciso Rodriguez", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/narciso-rodriguez.webp?v=1707315173", "url" => "/collections/narciso-rodriguez"],
        ["name" => "Nishane", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/nishane.webp?v=1707315196", "url" => "/collections/nishane"],
        ["name" => "Ormonde Jayne", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ormonde-jayne.webp?v=1707315218", "url" => "/collections/ormonde-jayne"],
        ["name" => "Paco Rabanne", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/paco-rabanne.webp?v=1707315241", "url" => "/collections/paco-rabanne"],
        ["name" => "Parfums de Marly", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/parfums-de-marly.webp?v=1707315263", "url" => "/collections/parfums-de-marly"],
        ["name" => "Penhaligon's", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/penhaligons.webp?v=1707315286", "url" => "/collections/penhaligons"],
        ["name" => "Prada", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/prada.webp?v=1707315308", "url" => "/collections/prada"],
        ["name" => "Ralph Lauren", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/ralph-lauren.webp?v=1707315331", "url" => "/collections/ralph-lauren"],
        ["name" => "Rasasi", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/rasasi.webp?v=1707315353", "url" => "/collections/rasasi"],
        ["name" => "Roberto Cavalli", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/roberto-cavalli.webp?v=1707315376", "url" => "/collections/roberto-cavalli"],
        ["name" => "Roja Parfums", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/roja-parfums.webp?v=1707315398", "url" => "/collections/roja-parfums"],
        ["name" => "Salvatore Ferragamo", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/salvatore-ferragamo.webp?v=1707315421", "url" => "/collections/salvatore-ferragamo"],
        ["name" => "Serge Lutens", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/serge-lutens.webp?v=1707315443", "url" => "/collections/serge-lutens"],
        ["name" => "Swiss Arabian", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/swiss-arabian.webp?v=1707315466", "url" => "/collections/swiss-arabian"],
        ["name" => "Thierry Mugler", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/thierry-mugler.webp?v=1707315488", "url" => "/collections/thierry-mugler"],
        ["name" => "Tiffany & Co.", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/tiffany-co.webp?v=1707315511", "url" => "/collections/tiffany-co"],
        ["name" => "Tom Ford", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/tom-ford.webp?v=1707315533", "url" => "/collections/tom-ford"],
        ["name" => "Tommy Hilfiger", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/tommy-hilfiger.webp?v=1707315556", "url" => "/collections/tommy-hilfiger"],
        ["name" => "Valentino", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/valentino.webp?v=1707315578", "url" => "/collections/valentino"],
        ["name" => "Van Cleef & Arpels", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/van-cleef-arpels.webp?v=1707315601", "url" => "/collections/van-cleef-arpels"],
        ["name" => "Versace", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/versace.webp?v=1707315623", "url" => "/collections/versace"],
        ["name" => "Viktor & Rolf", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/viktor-rolf.webp?v=1707315646", "url" => "/collections/viktor-rolf"],
        ["name" => "Xerjoff", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/xerjoff.webp?v=1707315668", "url" => "/collections/xerjoff"],
        ["name" => "Yves Saint Laurent", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/yves-saint-laurent.webp?v=1707315691", "url" => "/collections/yves-saint-laurent"],
        ["name" => "Zadig & Voltaire", "img" => "https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/zadig-voltaire.webp?v=1707315713", "url" => "/collections/zadig-voltaire"],
    ];

    private $imported = 0;
    private $skipped = 0;
    private $failed = 0;

    public function handle()
    {
        $this->info('Starting brand logo import from Shopify CDN...');
        $this->info('Total brands to process: ' . count($this->brandData));
        $this->newLine();

        $bar = $this->output->createProgressBar(count($this->brandData));
        $bar->start();

        foreach ($this->brandData as $brandData) {
            $this->processBrand($brandData);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Import completed!");
        $this->info("   Imported: {$this->imported}");
        $this->info("   Skipped: {$this->skipped}");
        $this->info("   Failed: {$this->failed}");

        return 0;
    }

    private function processBrand(array $data)
    {
        $brandName = $data['name'];
        $imageUrl = $data['img'];

        // Try exact match first (case-insensitive)
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();

        // If no exact match, try partial match (brand name contains or is contained in import name)
        if (!$brand) {
            $brand = Brand::where(function($query) use ($brandName) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($brandName) . '%'])
                      ->orWhereRaw('LOWER(?) LIKE CONCAT("%", LOWER(name), "%")', [$brandName]);
            })->first();
        }

        // Handle special name mappings
        if (!$brand) {
            $nameMap = [
                'Christian Dior' => 'Dior',
                'Roja Parfums' => 'Roja',
                'Hermès' => 'Hermes',
                'Lancôme' => 'Lancome',
                'Penhaligon\'s' => 'Penhaligon',
                'Estée Lauder' => 'Estee Lauder',
                'Chloé' => 'Chloe',
                'Frédéric Malle' => 'Frederic Malle',
            ];

            if (isset($nameMap[$brandName])) {
                $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($nameMap[$brandName])])->first();
            }
        }

        if (!$brand) {
            $this->skipped++;
            return;
        }

        // Skip if brand already has a logo
        if ($brand->logo) {
            $this->skipped++;
            return;
        }

        try {
            // Download image from Shopify CDN
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                // Log failure reason for debugging
                \Log::warning("Failed to download logo for {$brand->name}: HTTP {$response->status()}");
                $this->failed++;
                return;
            }

            // Get file extension from URL or response headers
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (!$extension || $extension === 'webp') {
                $extension = 'webp'; // Default to webp for Shopify images
            }

            // Generate filename from brand slug
            $filename = $brand->slug . '.' . $extension;

            // Save to storage/app/public/brands/
            $path = 'brands/' . $filename;
            Storage::disk('public')->put($path, $response->body());

            // Update brand with logo path
            $brand->update(['logo' => $path]);

            \Log::info("Successfully imported logo for {$brand->name}");
            $this->imported++;

        } catch (\Exception $e) {
            \Log::error("Exception importing logo for {$brand->name}: " . $e->getMessage());
            $this->failed++;
        }
    }
}
