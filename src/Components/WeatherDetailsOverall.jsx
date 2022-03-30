import React from "react";
import { FaWind } from "react-icons/fa";
import { formatAMPM, getDayInText } from "../Utils/functions";
import WindDir from "./WindDir";

export default function WeatherDetailsOverall({ weather}) {
  return (
    <div className="flex flex-col items-center space-y-2">
      <img src={weather?.condition?.icon} alt="icon" />
      <div className="w-full flex justify-between">
        <div className="text-left">
          <p>
            <span className="text-lg font-bold">
              {weather.daily_chance_of_rain}%
            </span>{" "}
            chance to rain
          </p>
          <p>
            <span className="text-lg font-bold">
              {weather.daily_chance_of_snow}%
            </span>{" "}
            chance to snow
          </p>
        </div>
        <div className="text-right">
          <p>
            Avg Temp:{" "}
            <span className="text-2xl font-bold">
              {weather.avgtemp_c}
              <sup>o</sup>c
            </span>
          </p>
          <p>
            Max Temp:{" "}
            <span className="text-xl font-bold">
              {weather.maxtemp_c}
              <sup>o</sup>c
            </span>
          </p>
          <p>
            Min Temp:{" "}
            <span className="text-xl font-bold">
              {weather.mintemp_c}
              <sup>o</sup>c
            </span>
          </p>
        </div>
      </div>
    </div>
  );
}
