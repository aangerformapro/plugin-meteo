
import VanillaAutoComplete from './vanilla-autocomplete.js';


const data = JSON.parse(document.querySelector('#data-communes').innerHTML), map = new Map();


data.forEach((item) =>
{

    map.set(`${item.nom} (${item.departement})`, item);

});

document.querySelector('#add-shortcode-form')?.addEventListener('submit', e => e.preventDefault());


const
    inputCity = document.querySelector('[name="nom_ville"]'),
    inputShortcode = document.querySelector('[name="meteo_shortcode"]'),
    copyBtn = document.querySelector("#copy-to-clipboard"),
    values = [...map.keys()];
let selectedEntry;
new VanillaAutoComplete({
    selector: inputCity,
    minChars: 3,
    source(typed, fn)
    {
        fn(values.filter(val => val.toLowerCase().startsWith(
            typed.toLowerCase()
        )));
    },
    onSelect: (selected) =>
    {

        if (selectedEntry = map.get(selected))
        {
            inputCity.value = selectedEntry.nom;
        }
    }
});


inputCity?.addEventListener('change', e =>
{
    e.preventDefault();

    if (!selectedEntry)
    {
        inputShortcode.value = '';
        copyBtn.disabled = true;
    }

    else
    {
        inputShortcode.value = `[meteo ville="${selectedEntry.nom}"]`;
        copyBtn.disabled = null;
    }

});


copyBtn?.addEventListener("click", e =>
{
    e.preventDefault();

    inputShortcode.select();
    inputShortcode.setSelectionRange(0, 99999);



    try
    {
        navigator.clipboard.writeText(inputShortcode.value);
    } catch (err)
    {
        document.execCommand('copy');
    }



    copyBtn.disabled = true;


});

