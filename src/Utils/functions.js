export function extractLocationText(location) {
  return `${location.country}, ${location.region}`;
}

export function formatAMPM(dt) {
  const date = new Date(dt);
  var hours = date.getHours();
  var minutes = date.getMinutes();
  var ampm = hours >= 12 ? "pm" : "am";
  hours = hours % 12;
  hours = hours ? hours : 12; // the hour '0' should be '12'
  minutes = minutes < 10 ? "0" + minutes : minutes;
  var strTime = hours + ":" + minutes + " " + ampm;
  return strTime;
}

const weekday = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];
export function getDayInText(dt) {
  const date = new Date(dt);
  return weekday[date.getDay()];
}

const months = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];
export function getMonthInText(dt) {
  const date = new Date(dt);
  return months[date.getMonth()];
}

const ordinals = ["st", "nd", "rd"];
export function getDateForHeader(dt) {
  const date = new Date(dt);
  let dayOfMonth = date.getDate();
  let ordinal = dayOfMonth < 4 ? ordinals[dayOfMonth - 1] : "th";

  return (
    <>
      {getDayInText(date)}, {dayOfMonth}
      <sup>{ordinal}</sup> of {getMonthInText(date)}
    </>
  );
}

export function locationOneLiner(location) {
  return `${location.name}, ${location.country}, ${location.region}`
}
