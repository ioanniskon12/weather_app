import React from "react";
import { formatAMPM, getDayInText } from "../Utils/functions";

export default function WeatherCard({ weather }) {
  return (
    <div className="p-4 rounded-md w-fit flex flex-col items-center space-y-4">
      <h1 className="font-bold text-3xl">{formatAMPM(new Date())} - {getDayInText(new Date())}</h1>
      <h3 className="font-semibold text-xl w-full">{weather.condition.text}</h3>
      <img src={weather.condition.icon} alt="weather" />
      <div className="text-right w-full">
        <p>Temp: <span className="text-2xl font-bold">{weather.temp_c}<sup>o</sup>c</span></p>
        <p>Feels like: <span className="text-xl font-bold">{weather.feelslike_c}<sup>o</sup>c</span></p>
      </div>
    </div>
  );
}
