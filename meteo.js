
import VanillaAutoComplete from './vanilla-autocomplete.js';


const map = new Map(
    JSON.parse(document.querySelector('#data-communes').innerHTML)
);



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
        inputShortcode.disabled = true;
        copyBtn.disabled = true;
    }

    else
    {

        inputShortcode.disabled = null;
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

document.querySelector('#add-shortcode-form')?.addEventListener('submit', e => e.preventDefault());