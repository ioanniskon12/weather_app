import React, { useEffect, useState } from "react";
import { useMutation } from "react-query";
import useDebounce from "../Hooks/useDebounce";
import agent from "../Utils/agent";
import { locationOneLiner } from "../Utils/functions";

export default function SearchInput({ value, onChange, name }) {
  const { data, mutate, isLoading } = useMutation((value) => agent.Weather.autocomplete(value));
  const debouncedValue = useDebounce(value, 500);
  useEffect(() => {
    if (debouncedValue) {
      mutate(debouncedValue);
    }
  }, [debouncedValue, mutate])
  return (
    <>
      <input
        name={name}
        type="text"
        placeholder="Enter location"
        className="p-2 rounded-md text-slate-900 font-semibold"
        value={value}
        onChange={onChange}
        autoComplete="off"
        list={name}
      />
      <datalist id={name}>
        {!isLoading && data && data.map((pred) => (
          <option value={locationOneLiner(pred)} />
        ))}
      </datalist>
    </>
  );
}
