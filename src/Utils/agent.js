const WEATHERAPI_KEY = "ad41d18530944ca497a142717223001";

const Weather = {
  getForecast: (location, days = 3) =>
    fetch(
      `http://api.weatherapi.com/v1/forecast.json?key=${WEATHERAPI_KEY}&q=${location}&days=${days}&aqi=no&alerts=no`
    ).then((res) => {
      if (res.ok) {
        return res.json();
      }
      throw new Error("An error occured");
    }),
  autocomplete: (input) =>
    fetch(
      `http://api.weatherapi.com/v1/search.json?key=ad41d18530944ca497a142717223001&q=${input}`
    ).then((res) => {
      if (res.ok) {
        return res.json();
      }
      throw new Error("An error occured");
    }),
};

export default {
  Weather,
};
