<?php
// Cartoon-style food court section with random mascot
$mascots = [
  '/assets/svg/mascot-burger.svg',
  '/assets/svg/mascot-pizza.svg',
  '/assets/svg/mascot-chef.svg',
  '/assets/svg/mascot-fries.svg',
  '/assets/svg/mascot-drink.svg'
];
$mascot = $mascots[array_rand($mascots)];
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $mascot)) {
  $mascot = '/assets/svg/mascot-burger.svg'; // fallback
}
?>
<section class="food-court">
  <div class="mascot" style="text-align:center; margin-bottom:1rem;">
    <img src="<?php echo $mascot; ?>" alt="Cartoon Mascot" class="mascot-img">
  </div>
  <article class="menu-board">
    <h2>Today's Specials</h2>
    <ul>
      <li>Cheesy Burger <span style="float:right;">₱99</span></li>
      <li>Veggie Wrap <span style="float:right;">₱79</span></li>
      <li>Fruit Shake <span style="float:right;">₱49</span></li>
    </ul>
  </article>
</section> 