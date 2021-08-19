{*
  * PrestaPay - A Sample Payment Module for PrestaShop 1.7
  *
  * HTML to be displayed in the order confirmation page
  *
  * @author Andresa Martins <contact@andresa.dev>
  * @license https://opensource.org/licenses/afl-3.0.php
  *}
 <p><em>Attendiamo la ricarica della nostra carta per poter effettuare la spedizione. Grazie per il tuo acquisto.</em></p>
 <ul>
  <li>{l s='Numero carta da ricaricare:'} <strong>{$ppnr}</strong></li>
  <li>{l s='Titolare della carta:'} <strong>{$owner_name}</strong></li>
  <li>{l s='Codice Fiscale del titolare:'} <strong>{$owner_cf}</strong></li>
</ul>